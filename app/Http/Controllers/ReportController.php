<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Shop;
use App\Models\Purchase;
use App\Models\Transfer;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $type = $request->get('type', 'financial');
        $period = $request->get('period', 'daily');
        $from = $request->get('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->get('to') ?: now()->toDateString();

        // Warehouse managers are hard-scoped to their own warehouse.
        $warehouseId = $user->isWarehouseManager() && $user->warehouse_id
            ? $user->warehouse_id
            : $request->get('warehouse_id');
        $scope = $warehouseId ? (int) $warehouseId : null;
        $scopeShopIds = $scope ? Shop::where('warehouse_id', $scope)->pluck('id') : collect();
        $scopeSales = fn ($q) => $q->when($scope, fn ($w) => $w->whereHas('shop', fn ($s) => $s->where('warehouse_id', $scope)));
        $scopeTransfers = fn ($q) => $q->when($scope, fn ($w) => $w->where(fn ($t) => $t
            ->where('from_warehouse_id', $scope)
            ->orWhere('to_warehouse_id', $scope)
            ->orWhereIn('to_shop_id', $scopeShopIds)));
        $scopeStocks = fn ($q) => $q->when($scope, fn ($w) => $w->where('stockable_type', Warehouse::class)->where('stockable_id', $scope));

        $expr = match ($period) {
            'yearly' => "DATE_FORMAT(sale_date, '%Y')",
            'monthly' => "DATE_FORMAT(sale_date, '%Y-%m')",
            default => "DATE(sale_date)",
        };

        $salesSeries = Sale::whereBetween('sale_date', [$from, $to])
            ->when($scope, fn ($q) => $q->whereHas('shop', fn ($s) => $s->where('warehouse_id', $scope)))
            ->select(DB::raw("({$expr}) as period"))
            ->addSelect(DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as orders'))
            ->groupBy(DB::raw($expr))->orderBy(DB::raw($expr))->get();

        $purchaseExpr = match ($period) {
            'yearly' => "DATE_FORMAT(purchase_date, '%Y')",
            'monthly' => "DATE_FORMAT(purchase_date, '%Y-%m')",
            default => "DATE(purchase_date)",
        };

        $purchaseSeries = Purchase::whereBetween('purchase_date', [$from, $to])
            ->when($scope, fn ($q) => $q->where('warehouse_id', $scope))
            ->select(DB::raw("({$purchaseExpr}) as period"))
            ->addSelect(DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as invoices'))
            ->groupBy(DB::raw($purchaseExpr))->orderBy(DB::raw($purchaseExpr))->get();

        $categorySales = Sale::join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->join('product_variants', 'product_variants.id', '=', 'sale_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->when($scope, fn ($q) => $q->whereHas('shop', fn ($s) => $s->where('warehouse_id', $scope)))
            ->select('categories.name as category')
            ->addSelect(DB::raw('SUM(sale_items.total) as total'))
            ->groupBy('categories.name')->orderByDesc('total')->get();

        $financials = [
            'sales_total' => (float) $scopeSales(Sale::whereBetween('sale_date', [$from, $to]))->sum('total'),
            'sales_count' => $scopeSales(Sale::whereBetween('sale_date', [$from, $to]))->count(),
            'purchase_total' => (float) Purchase::whereBetween('purchase_date', [$from, $to])->when($scope, fn ($q) => $q->where('warehouse_id', $scope))->sum('total'),
            'purchase_count' => Purchase::whereBetween('purchase_date', [$from, $to])->when($scope, fn ($q) => $q->where('warehouse_id', $scope))->count(),
            'receivable' => (float) (LedgerEntry::where('entry_type', 'payable')->sum('debit') - LedgerEntry::where('entry_type', 'payable')->sum('credit')),
            'gross_margin' => (float) $scopeSales(Sale::whereBetween('sale_date', [$from, $to]))->sum('total')
                - (float) $scopeSales(Sale::join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('product_variants', 'product_variants.id', '=', 'sale_items.product_variant_id')
                    ->whereBetween('sales.sale_date', [$from, $to]))
                    ->select(DB::raw('SUM(sale_items.quantity * product_variants.cost_price) as c'))->value('c'),
            'transfers' => $scopeTransfers(Transfer::whereBetween('created_at', [$from, now()]))->count(),
            'delivered' => $scopeTransfers(Transfer::where('status', 'delivered')->whereBetween('delivered_at', [$from, now()]))->count(),
        ];

        $inventoryByWarehouse = Warehouse::withSum('stocks as units', 'quantity')
            ->when($scope, fn ($q) => $q->whereKey($scope))
            ->orderByDesc('units')->get();

        $inventoryByShop = Shop::withSum('stocks as units', 'quantity')
            ->when($scope, fn ($q) => $q->where('warehouse_id', $scope))
            ->orderByDesc('units')->get();

        $stockValue = (float) $scopeStocks(Stock::join('product_variants', 'product_variants.id', '=', 'stocks.product_variant_id'))
            ->select(DB::raw('SUM(stocks.quantity * product_variants.cost_price) as v'))->value('v');

        $retailValue = (float) $scopeStocks(Stock::join('product_variants', 'product_variants.id', '=', 'stocks.product_variant_id'))
            ->select(DB::raw('SUM(stocks.quantity * product_variants.price) as v'))->value('v');

        $topProducts = $scopeStocks(Stock::join('product_variants', 'product_variants.id', '=', 'stocks.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id'))
            ->select('products.name', DB::raw('SUM(stocks.quantity) as units'))
            ->groupBy('products.name')->orderByDesc('units')->take(8)->get();

        $warehouses = Warehouse::when($scope, fn ($q) => $q->whereKey($scope))->orderBy('name')->get();

        return view('reports.index', compact(
            'type', 'period', 'from', 'to', 'warehouseId',
            'salesSeries', 'purchaseSeries', 'categorySales', 'financials',
            'inventoryByWarehouse', 'inventoryByShop', 'stockValue', 'retailValue',
            'topProducts', 'warehouses'
        ));
    }
}
