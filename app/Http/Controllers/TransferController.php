<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Shop;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\GeoService;
use App\Support\NumberGenerator;
use App\Support\TransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransferController extends Controller
{
    public function __construct(protected TransferService $service) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Transfer::with(['fromWarehouse', 'toWarehouse', 'toShop', 'rider'])
            ->when($request->q, fn ($q, $v) => $q->where('transfer_no', 'like', "%{$v}%"))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->direction, fn ($q, $v) => $q->where('direction', $v));

        if ($user->isWarehouseManager() && $user->warehouse_id) {
            $shopIds = Shop::where('warehouse_id', $user->warehouse_id)->pluck('id');
            $query->where(fn ($q) => $q->where('from_warehouse_id', $user->warehouse_id)
                ->orWhere('to_warehouse_id', $user->warehouse_id)
                ->orWhereIn('to_shop_id', $shopIds));
        } elseif ($user->isShopkeeper() && $user->shop_id) {
            $query->where('to_shop_id', $user->shop_id);
        } else {
            $query->when($request->from_warehouse_id, fn ($q, $v) => $q->where('from_warehouse_id', $v))
                ->when($request->to_id, function ($q, $v) {
                    $q->where(fn ($w) => $w->where('to_warehouse_id', $v)->orWhere('to_shop_id', $v));
                });
        }

        $transfers = $query->latest()->paginate(20)->withQueryString();

        $warehouses = Warehouse::orderBy('name')->get();
        $shops = Shop::orderBy('name')->get();

        return view('distribution.transfers.index', compact('transfers', 'warehouses', 'shops'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $scoped = $user->isWarehouseManager() && $user->warehouse_id;

        $sourceWarehouses = $scoped
            ? Warehouse::whereKey($user->warehouse_id)->get()
            : Warehouse::where('status', 'active')->orderBy('name')->get();

        return view('distribution.transfers.form', [
            'sourceWarehouses' => $sourceWarehouses,
            'regionalWarehouses' => Warehouse::where('type', 'regional')->orderBy('name')->get(),
            'shops' => Shop::where('status', 'active')
                ->when($scoped, fn ($q) => $q->where('warehouse_id', $user->warehouse_id))
                ->orderBy('name')->get(),
            'riders' => User::where('role', User::ROLE_RIDER)->where('status', 'active')
                ->when($scoped, fn ($q) => $q->where('warehouse_id', $user->warehouse_id))
                ->orderBy('name')->get(),
            'destinations' => $this->variantOptions(),
        ]);
    }

    public function store(Request $request, TransferService $service)
    {
        $data = $request->validate([
            'direction' => ['required', 'in:warehouse_to_warehouse,warehouse_to_shop'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'to_shop_id' => ['nullable', 'exists:shops,id'],
            'rider_id' => ['nullable', 'exists:users,id'],
            'payment_mode' => ['required', 'in:full_cash,credit,logistics_line'],
            'expected_date' => ['nullable', 'date'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ], [
            'items.required' => 'Add at least one item to the transfer.',
        ]);

        if ($data['direction'] === 'warehouse_to_warehouse' && empty($data['to_warehouse_id'])) {
            return back()->withErrors(['to_warehouse_id' => 'Select the destination warehouse.'])->withInput();
        }
        if ($data['direction'] === 'warehouse_to_shop' && empty($data['to_shop_id'])) {
            return back()->withErrors(['to_shop_id' => 'Select the destination shop.'])->withInput();
        }

        $user = auth()->user();
        if ($user->isWarehouseManager()) {
            if (! $user->warehouse_id || (int) $data['from_warehouse_id'] !== (int) $user->warehouse_id) {
                return back()->withErrors(['from_warehouse_id' => 'You can only create transfers from your own warehouse.'])->withInput();
            }
            if (! empty($data['to_shop_id'])) {
                $shopWarehouseId = Shop::whereKey($data['to_shop_id'])->value('warehouse_id');
                if ((int) $shopWarehouseId !== (int) $user->warehouse_id) {
                    return back()->withErrors(['to_shop_id' => 'You can only transfer to shops under your own warehouse.'])->withInput();
                }
            }
        }

        if (! empty($data['rider_id'])) {
            $rider = User::find($data['rider_id']);
            if (! $this->riderAcceptsDelivery($rider, (int) $data['from_warehouse_id'], $data['to_shop_id'] ?? null)) {
                return back()->withErrors(['rider_id' => 'Riders can only be assigned to deliveries within their own warehouse and city.'])->withInput();
            }
        }

        $transfer = DB::transaction(function () use ($data, $service) {
            $subtotal = 0;
            $itemCount = 0;

            foreach ($data['items'] as $item) {
                $variant = \App\Models\ProductVariant::with('product')->find($item['product_variant_id']);
                $line = $item['quantity'] * $variant->price;
                $subtotal += $line;
                $itemCount += $item['quantity'];
            }

            $shipping = $data['shipping_cost'] ?? 0;

            $transfer = Transfer::create([
                'transfer_no' => NumberGenerator::transfer(),
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['direction'] === 'warehouse_to_warehouse' ? $data['to_warehouse_id'] : null,
                'to_shop_id' => $data['direction'] === 'warehouse_to_shop' ? $data['to_shop_id'] : null,
                'rider_id' => $data['rider_id'] ?? null,
                'created_by' => auth()->id(),
                'direction' => $data['direction'],
                'status' => 'pending',
                'item_count' => $itemCount,
                'subtotal' => $subtotal,
                'shipping_cost' => $shipping,
                'total' => $subtotal + $shipping,
                'payment_mode' => $data['payment_mode'],
                'expected_date' => $data['expected_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $variant = \App\Models\ProductVariant::find($item['product_variant_id']);
                TransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $variant->price,
                    'total' => $variant->price * $item['quantity'],
                ]);
            }

            if (! empty($data['rider_id'])) {
                $service->syncDelivery($transfer, (int) $data['rider_id']);
                $transfer->update(['status' => 'assigned']);
            }

            return $transfer;
        });

        return redirect()->route('transfers.show', $transfer)->with('success', 'Transfer created successfully.');
    }

    public function show(Transfer $transfer)
    {
        abort_unless($this->transferInScope($transfer), 403, 'You are not authorized to view this transfer.');

        $transfer->load([
            'fromWarehouse', 'toWarehouse', 'toShop', 'rider', 'creator',
            'items.productVariant.product.brand', 'deliveries.rider',
        ]);

        $riders = User::where('role', User::ROLE_RIDER)->where('status', 'active')
            ->when($transfer->from_warehouse_id, fn ($q) => $q->where('warehouse_id', $transfer->from_warehouse_id))
            ->orderBy('name')->get();
        $liveLocations = \App\Models\RiderLocation::whereIn('rider_id', $transfer->deliveries->pluck('rider_id'))
            ->latest()->get()->groupBy('rider_id');

        return view('distribution.transfers.show', compact('transfer', 'riders', 'liveLocations'));
    }

    public function assign(Request $request, Transfer $transfer, TransferService $service)
    {
        abort_unless($this->transferInScope($transfer), 403, 'You are not authorized to manage this transfer.');

        $data = $request->validate([
            'rider_id' => ['required', 'exists:users,id'],
        ]);

        $rider = User::find($data['rider_id']);
        if (! $this->riderAcceptsDelivery($rider, (int) $transfer->from_warehouse_id, $transfer->to_shop_id)) {
            return back()->withErrors(['rider_id' => 'Riders can only be assigned to deliveries within their own warehouse and city.']);
        }

        if (! empty($transfer->rider_id) && $transfer->rider_id != $data['rider_id'] && $transfer->status !== 'pending') {
            return back()->with('error', 'Reassign only before dispatch.');
        }

        DB::transaction(function () use ($transfer, $data, $service) {
            $transfer->update(['rider_id' => $data['rider_id']]);
            $service->syncDelivery($transfer, (int) $data['rider_id']);

            if ($transfer->status === 'pending') {
                $transfer->update(['status' => 'assigned']);
            }
        });

        return back()->with('success', 'Rider assigned to transfer.');
    }

    public function dispatch(Transfer $transfer, TransferService $service)
    {
        abort_unless($this->transferInScope($transfer), 403, 'You are not authorized to manage this transfer.');

        try {
            $service->dispatch($transfer);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfer dispatched and stock released from source.');
    }

    public function deliver(Transfer $transfer, TransferService $service)
    {
        abort_unless($this->transferInScope($transfer), 403, 'You are not authorized to manage this transfer.');

        try {
            $service->deliver($transfer);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfer received and stock added to destination.');
    }

    public function cancel(Transfer $transfer, TransferService $service)
    {
        abort_unless($this->transferInScope($transfer), 403, 'You are not authorized to manage this transfer.');

        try {
            $service->cancel($transfer);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfer cancelled.');
    }

    public function destroy(Transfer $transfer)
    {
        abort_unless($this->transferInScope($transfer), 403, 'You are not authorized to manage this transfer.');

        if (! in_array($transfer->status, ['pending', 'assigned'], true)) {
            return back()->with('error', 'Only pending transfers can be deleted.');
        }

        $transfer->delete();

        return back()->with('success', 'Transfer deleted.');
    }

    protected function variantOptions()
    {
        return \App\Models\ProductVariant::with('product.brand')->where('status', 'active')->get()
            ->sortBy(fn ($v) => $v->product?->name)->values();
    }

    /** Shopkeepers may only touch transfers to their shop; warehouse managers only transfers involving their warehouse. */
    protected function transferInScope(Transfer $transfer): bool
    {
        $user = auth()->user();

        if ($user->isShopkeeper()) {
            return $transfer->to_shop_id !== null && (int) $transfer->to_shop_id === (int) $user->shop_id;
        }

        if ($user->isWarehouseManager()) {
            if (! $user->warehouse_id) {
                return false;
            }

            if (in_array((int) $user->warehouse_id, array_map('intval', [$transfer->from_warehouse_id, $transfer->to_warehouse_id]), true)) {
                return true;
            }

            if ($transfer->to_shop_id) {
                return (int) Shop::whereKey($transfer->to_shop_id)->value('warehouse_id') === (int) $user->warehouse_id;
            }

            return false;
        }

        return true;
    }

    /** A rider may only serve deliveries leaving from their own warehouse, and only within their warehouse's city. */
    protected function riderAcceptsDelivery(?User $rider, int $fromWarehouseId, ?int $toShopId): bool
    {
        if (! $rider
            || $rider->role !== User::ROLE_RIDER
            || $rider->status !== 'active'
            || ! $rider->warehouse_id
            || (int) $rider->warehouse_id !== $fromWarehouseId) {
            return false;
        }

        if ($toShopId) {
            $shopCity = Shop::whereKey($toShopId)->value('city');
            $riderCity = Warehouse::whereKey($rider->warehouse_id)->value('city');
            if ($shopCity && $riderCity && strcasecmp(trim($shopCity), trim($riderCity)) !== 0) {
                return false;
            }
        }

        return true;
    }
}
