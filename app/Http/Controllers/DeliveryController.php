<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Delivery::with(['transfer.fromWarehouse', 'transfer.toShop', 'transfer.toWarehouse', 'rider', 'statusHistories'])
            ->when($request->q, fn ($q, $v) => $q->where('delivery_no', 'like', "%{$v}%"))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->type, fn ($q, $v) => $q->where('type', $v));

        if ($user->isWarehouseManager() && $user->warehouse_id) {
            $shopIds = Shop::where('warehouse_id', $user->warehouse_id)->pluck('id');
            $query->whereHas('transfer', fn ($t) => $t
                ->where('from_warehouse_id', $user->warehouse_id)
                ->orWhere('to_warehouse_id', $user->warehouse_id)
                ->orWhereIn('to_shop_id', $shopIds));
        } elseif ($user->isRider()) {
            $query->where('rider_id', $user->id);
        }

        $deliveries = $query->latest()->paginate(20)->withQueryString();
        $riders = User::where('role', User::ROLE_RIDER)->where('status', 'active')->orderBy('name')->get();

        return view('logistics.deliveries.index', compact('deliveries', 'riders'));
    }

    public function show(Delivery $delivery)
    {
        $this->authorizeDeliveryScope($delivery);

        $delivery->load(['transfer.items.productVariant.product', 'rider', 'statusHistories.user', 'locations' => fn ($q) => $q->latest()->take(20)]);

        return view('logistics.deliveries.show', compact('delivery'));
    }

    public function updateStatus(Request $request, Delivery $delivery, \App\Support\TransferService $service)
    {
        $this->authorizeDeliveryScope($delivery);

        if ($delivery->isTerminal()) {
            return back()->with('error', 'This delivery has already been marked as ' . $delivery->status_label . ' and can no longer be updated.');
        }

        $data = $request->validate([
            'status' => [
                'required',
                'in:' . implode(',', Delivery::ALL_STATUSES),
                function ($attribute, $value, $fail) use ($delivery) {
                    if ($value === $delivery->status) {
                        $fail('This delivery is already marked as ' . $delivery->status_label . '.');
                    } elseif (! $delivery->canTransitionTo($value)) {
                        $fail('Status cannot move backwards from "' . $delivery->status_label . '" to "' . ucwords(str_replace('_', ' ', $value)) . '".');
                    }
                },
            ],
            'note' => ['required_if:status,cancelled', 'required_if:failure_code,other', 'nullable', 'string', 'max:255'],
            'failure_code' => ['required_if:status,failed', 'in:' . implode(',', array_keys(Delivery::FAILURE_REASONS))],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ], [
            'note.required_if' => 'A cancellation reason is required when cancelling, and a comment is required for the "Other" failure reason.',
            'failure_code.required_if' => 'A failure reason is required when marking a delivery as failed.',
            'failure_code.in' => 'The selected failure reason is invalid.',
        ]);

        $transfer = $delivery->transfer;

        DB::transaction(function () use ($delivery, $data, $transfer, $service) {
            $current = Delivery::whereKey($delivery->id)->lockForUpdate()->first();

            if ($current->isTerminal()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'status' => 'This delivery has already been marked as ' . $current->status_label . ' and can no longer be updated.',
                ]);
            }

            if (! $current->canTransitionTo($data['status'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'status' => 'Status cannot move backwards from "' . $current->status_label . '" to "' . ucwords(str_replace('_', ' ', $data['status'])) . '".',
                ]);
            }

            $payload = [
                'status' => $data['status'],
                'dispatched_at' => ($data['status'] === Delivery::STATUS_DISPATCHED) ? now() : $delivery->dispatched_at,
                'delivered_at' => ($data['status'] === Delivery::STATUS_DELIVERED) ? now() : $delivery->delivered_at,
                'picked_at' => ($data['status'] === Delivery::STATUS_PICKED_UP) ? now() : $delivery->picked_at,
            ];

            $historyNote = $data['note'] ?? 'Status updated';

            if ($data['status'] === Delivery::STATUS_FAILED) {
                $payload['failure_reason'] = $data['failure_code'];
                $label = Delivery::FAILURE_REASONS[$data['failure_code']];
                $historyNote = ($data['note'] ?? null) ? $label . ' — ' . $data['note'] : $label;
            } elseif ($data['status'] === Delivery::STATUS_CANCELLED) {
                $payload['failure_reason'] = $data['note'];
                $historyNote = 'Cancelled — ' . $data['note'];
            } elseif ($data['status'] === Delivery::STATUS_IN_TRANSIT && $current->status === Delivery::STATUS_FAILED) {
                $historyNote = (($data['note'] ?? null) ?: 'Retrying delivery') . ' (retry after failed attempt)';
            }

            $delivery->update($payload);

            $service->logStatus($delivery, $data['status'], $historyNote);

            if ($transfer) {
                try {
                    match ($data['status']) {
                        Delivery::STATUS_DISPATCHED => $service->dispatch($transfer),
                        Delivery::STATUS_IN_TRANSIT => $service->markInTransit($transfer),
                        Delivery::STATUS_DELIVERED => $service->deliver($transfer),
                        Delivery::STATUS_CANCELLED => $service->cancel($transfer),
                        default => null,
                    };
                } catch (\RuntimeException $e) {
                    \Illuminate\Support\Facades\Log::warning('Transfer sync skipped: ' . $e->getMessage());
                }
            }
        });

        return back()->with('success', 'Delivery status updated.');
    }

    public function position(Delivery $delivery)
    {
        $this->authorizeDeliveryScope($delivery);

        $lastPing = $delivery->rider_id
            ? \App\Models\RiderLocation::latestPerRider([(int) $delivery->rider_id])->get((int) $delivery->rider_id)
            : null;

        $eta = \App\Support\GeoService::estimateEta(
            $lastPing?->latitude,
            $lastPing?->longitude,
            $delivery->drop_latitude,
            $delivery->drop_longitude,
            $lastPing ? \App\Models\RiderLocation::recentSpeedKmhFor((int) $delivery->rider_id) : null,
            $lastPing?->created_at,
        );

        return response()->json([
            'id' => $delivery->id,
            'no' => $delivery->delivery_no,
            'status' => $delivery->status,
            'terminal' => $delivery->isTerminal(),
            'rider' => $delivery->rider ? ['id' => $delivery->rider->id, 'name' => $delivery->rider->name] : null,
            'drop' => [
                'lat' => $delivery->drop_latitude,
                'lng' => $delivery->drop_longitude,
                'name' => $delivery->drop_name,
            ],
            'rider_pos' => $lastPing ? ['lat' => $lastPing->latitude, 'lng' => $lastPing->longitude] : null,
            'eta_minutes' => $eta['eta_minutes'],
            'distance_km' => $eta['distance_km'],
            'speed_kmh' => $eta['speed_kmh'],
            'stale' => $eta['stale'],
            'last_seen' => $eta['last_seen'],
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function storeLocation(Request $request)
    {
        $data = $request->validate([
            'delivery_id' => ['nullable', 'integer', 'exists:deliveries,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        $rider = auth()->user();

        $deliveryId = null;
        if (! empty($data['delivery_id'])) {
            $owned = Delivery::whereKey($data['delivery_id'])->where('rider_id', $rider->id)->exists();
            $deliveryId = $owned ? (int) $data['delivery_id'] : null;
        }

        if ($deliveryId === null) {
            $deliveryId = $rider->deliveries()
                ->whereIn('status', [
                    Delivery::STATUS_PICKED_UP,
                    Delivery::STATUS_DISPATCHED,
                    Delivery::STATUS_IN_TRANSIT,
                ])
                ->orderByDesc('updated_at')
                ->value('id');
        }

        $recent = \App\Models\RiderLocation::where('rider_id', $rider->id)->latest('id')->first();
        if ($recent && $recent->created_at->gt(now()->subSeconds(5))) {
            return response()->json(['ok' => true, 'throttled' => true]);
        }

        \App\Models\RiderLocation::create([
            'rider_id' => $rider->id,
            'delivery_id' => $deliveryId,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'speed' => $data['speed'] ?? null,
            'label' => $data['label'] ?? 'live',
        ]);

        return response()->json(['ok' => true]);
    }

    /** Riders own their deliveries; warehouse managers only deliveries belonging to their warehouse or its shops. */
    protected function authorizeDeliveryScope(Delivery $delivery): void
    {
        $user = auth()->user();

        if ($user->isRider()) {
            abort_unless((int) $delivery->rider_id === (int) $user->id, 403, 'You are not authorized to view this delivery.');

            return;
        }

        if ($user->isWarehouseManager()) {
            $transfer = $delivery->transfer;
            $inScope = $user->warehouse_id && $transfer && (
                (int) $transfer->from_warehouse_id === (int) $user->warehouse_id
                || (int) $transfer->to_warehouse_id === (int) $user->warehouse_id
                || ($transfer->to_shop_id && (int) Shop::whereKey($transfer->to_shop_id)->value('warehouse_id') === (int) $user->warehouse_id)
            );

            abort_unless($inScope, 403, 'You are not authorized to view this delivery.');
        }
    }
}
