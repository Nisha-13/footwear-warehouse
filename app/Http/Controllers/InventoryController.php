<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Support\StockService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Stock::with(['productVariant.product.brand', 'stockable'])
            ->when($request->q, function ($q, $v) {
                $q->whereHas('productVariant', function ($w) use ($v) {
                    $w->where('sku', 'like', "%{$v}%")->orWhere('size', 'like', "%{$v}%")
                        ->orWhere('color', 'like', "%{$v}%")
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$v}%")->orWhere('sku', 'like', "%{$v}%"));
                });
            })
            ->when($request->low_stock, fn ($q) => $q->whereColumn('quantity', '<=', 'reorder_level'));

        if ($user->isWarehouseManager() && $user->warehouse_id) {
            $query->where('stockable_type', Warehouse::class)->where('stockable_id', $user->warehouse_id);
        } elseif ($user->isShopkeeper() && $user->shop_id) {
            $query->where('stockable_type', Shop::class)->where('stockable_id', $user->shop_id);
        } else {
            $query->when($request->stockable_type, fn ($q, $v) => $q->where('stockable_type', $v === 'warehouse' ? Warehouse::class : Shop::class))
                ->when($request->stockable_id, fn ($q, $v) => $q->where('stockable_id', $v));
        }

        $stocks = $query->orderByDesc('quantity')->paginate(25)->withQueryString();

        $warehouses = Warehouse::orderBy('name')->get();
        $shops = Shop::orderBy('name')->get();

        return view('inventory.index', compact('stocks', 'warehouses', 'shops'));
    }

    public function adjust(Request $request, Stock $stock, StockService $service)
    {
        $user = auth()->user();
        if ($user->isWarehouseManager()) {
            $ownsStock = $user->warehouse_id
                && $stock->stockable_type === Warehouse::class
                && (int) $stock->stockable_id === (int) $user->warehouse_id;
            abort_unless($ownsStock, 403, 'You can only adjust stock for your own warehouse.');
        }

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $service->adjust($stock->productVariant, $stock->stockable, (int) $data['quantity'], [
            'note' => $data['note'] ?? 'Manual inventory adjustment',
        ]);

        return back()->with('success', 'Stock adjusted successfully.');
    }

    public function movements(Request $request)
    {
        $user = auth()->user();

        $movements = \App\Models\StockMovement::with(['productVariant.product', 'stockable', 'user'])
            ->when($user->isWarehouseManager() && $user->warehouse_id, fn ($q) => $q->where('stockable_type', Warehouse::class)->where('stockable_id', $user->warehouse_id))
            ->when($user->isShopkeeper() && $user->shop_id, fn ($q) => $q->where('stockable_type', Shop::class)->where('stockable_id', $user->shop_id))
            ->latest()->paginate(30);

        return view('inventory.movements', compact('movements'));
    }

    public function variantJson(ProductVariant $variant)
    {
        return response()->json([
            'id' => $variant->id,
            'label' => $variant->label,
            'price' => $variant->price,
            'cost_price' => $variant->cost_price,
        ]);
    }
}
