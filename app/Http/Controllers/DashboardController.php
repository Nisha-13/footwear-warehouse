<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\LedgerEntry;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return match ($user->role) {
            User::ROLE_SUPER_ADMIN => $this->superAdmin(),
            User::ROLE_WAREHOUSE_MANAGER => $this->warehouseManager(),
            User::ROLE_SHOPKEEPER => $this->shopkeeper(),
            User::ROLE_RIDER => redirect()->route('rider.dashboard'),
            User::ROLE_ACCOUNTANT => $this->accountant(),
            default => view('dashboard.generic'),
        };
    }

    protected function superAdmin()
    {
        $warehouses = Warehouse::with('manager')->withCount('shops')->get();
        $main = Warehouse::where('type', 'main')->first();

        $stats = [
            'purchases_month' => (float) Purchase::whereMonth('purchase_date', now()->month)->whereYear('purchase_date', now()->year)->sum('total'),
            'sales_month' => (float) Sale::whereMonth('sale_date', now()->month)->whereYear('sale_date', now()->year)->sum('total'),
            'receivable' => (float) (LedgerEntry::where('entry_type', 'payable')->sum('debit') - LedgerEntry::where('entry_type', 'payable')->sum('credit')),
            'total_stock' => (int) Stock::sum('quantity'),
            'warehouses' => Warehouse::count(),
            'shops' => Shop::count(),
            'products' => ProductVariant::count(),
            'riders' => User::where('role', User::ROLE_RIDER)->count(),
            'pending_deliveries' => Delivery::whereNotIn('status', ['delivered', 'cancelled', 'failed'])->count(),
            'transfers_transit' => Transfer::whereIn('status', ['dispatched', 'in_transit', 'assigned'])->count(),
        ];

        $recentPurchases = Purchase::with('supplier')->latest()->take(5)->get();
        $recentTransfers = Transfer::with(['toWarehouse', 'toShop', 'rider'])->latest()->take(5)->get();
        $activeDeliveries = Delivery::with('rider')->whereNotIn('status', ['delivered', 'cancelled'])->latest()->take(6)->get();

        $salesTrend = Sale::select(
            DB::raw('DATE(sale_date) as day'),
            DB::raw('SUM(total) as total')
        )->where('sale_date', '>=', now()->subDays(13))->groupBy('day')->orderBy('day')->get();

        $warehouseStock = Warehouse::withSum('stocks as stock_units', 'quantity')
            ->orderByDesc('stock_units')->take(5)->get();

        return view('dashboard.super_admin', compact(
            'stats', 'warehouses', 'main', 'recentPurchases', 'recentTransfers',
            'activeDeliveries', 'salesTrend', 'warehouseStock'
        ));
    }

    protected function warehouseManager()
    {
        $user = auth()->user();
        $warehouse = $user->warehouse;

        if (! $warehouse) {
            return view('dashboard.generic', ['message' => 'No warehouse assigned to your account yet.']);
        }

        $stats = [
            'stock_units' => (int) $warehouse->stocks()->sum('quantity'),
            'skus' => (int) $warehouse->stocks()->distinct('product_variant_id')->count('product_variant_id'),
            'shops' => $warehouse->shops()->count(),
            'incoming' => Transfer::where('to_warehouse_id', $warehouse->id)->whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'outgoing' => Transfer::where('from_warehouse_id', $warehouse->id)->whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'deliveries' => Delivery::whereHas('transfer', fn ($q) => $q->where('from_warehouse_id', $warehouse->id))
                ->whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'low_stock' => $warehouse->stocks()->whereColumn('quantity', '<=', 'reorder_level')->count(),
        ];

        $lowStock = $warehouse->stocks()->with('productVariant.product')
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->orderBy('quantity')->take(8)->get();

        $recentTransfers = Transfer::with(['toShop', 'toWarehouse', 'rider'])
            ->where(fn ($q) => $q->where('from_warehouse_id', $warehouse->id)->orWhere('to_warehouse_id', $warehouse->id))
            ->latest()->take(6)->get();

        $shops = $warehouse->shops()->withSum('stocks as stock_units', 'quantity')->get();

        return view('dashboard.warehouse_manager', compact('warehouse', 'stats', 'lowStock', 'recentTransfers', 'shops'));
    }

    protected function accountant()
    {
        $stats = [
            'purchases_month' => (float) Purchase::whereMonth('purchase_date', now()->month)->whereYear('purchase_date', now()->year)->sum('total'),
            'sales_month' => (float) Sale::whereMonth('sale_date', now()->month)->whereYear('sale_date', now()->year)->sum('total'),
            'receivable_outstanding' => (float) (LedgerEntry::sum('debit') - LedgerEntry::sum('credit')),
            'payables_total' => (float) LedgerEntry::sum('debit'),
            'credits_total' => (float) LedgerEntry::sum('credit'),
            'purchases_pending' => Purchase::whereIn('status', ['ordered', 'received_partial'])->count(),
            'suppliers' => \App\Models\Supplier::count(),
            'recent_invoices' => Purchase::count(),
        ];

        $recentPurchases = Purchase::with('supplier')->latest()->take(10)->get();
        $recentLedger = LedgerEntry::with(['party', 'user'])->latest('entry_date')->latest('id')->take(12)->get();
        $salesTrend = Sale::select(
            DB::raw('DATE(sale_date) as day'),
            DB::raw('SUM(total) as total')
        )->where('sale_date', '>=', now()->subDays(13))->groupBy('day')->orderBy('day')->get();

        return view('dashboard.accountant', compact('stats', 'recentPurchases', 'recentLedger', 'salesTrend'));
    }

    protected function shopkeeper()
    {
        $user = auth()->user();
        $shop = $user->shop;

        if (! $shop) {
            return view('dashboard.generic', ['message' => 'No shop assigned to your account yet.']);
        }

        $stats = [
            'stock_units' => (int) $shop->stocks()->sum('quantity'),
            'skus' => (int) $shop->stocks()->distinct('product_variant_id')->count('product_variant_id'),
            'sales_today' => (float) Sale::where('shop_id', $shop->id)->whereDate('sale_date', today())->sum('total'),
            'sales_month' => (float) Sale::where('shop_id', $shop->id)->whereMonth('sale_date', now()->month)->sum('total'),
            'incoming' => Transfer::where('to_shop_id', $shop->id)->whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'low_stock' => $shop->stocks()->whereColumn('quantity', '<=', 'reorder_level')->count(),
        ];

        $lowStock = $shop->stocks()->with('productVariant.product')
            ->whereColumn('quantity', '<=', 'reorder_level')->take(8)->get();

        $recentSales = Sale::where('shop_id', $shop->id)->latest()->take(6)->get();
        $incomingTransfers = Transfer::with(['fromWarehouse', 'rider'])
            ->where('to_shop_id', $shop->id)->latest()->take(6)->get();

        $salesTrend = Sale::where('shop_id', $shop->id)
            ->select(DB::raw('DATE(sale_date) as day'), DB::raw('SUM(total) as total'))
            ->where('sale_date', '>=', now()->subDays(13))->groupBy('day')->orderBy('day')->get();

        return view('dashboard.shopkeeper', compact('shop', 'stats', 'lowStock', 'recentSales', 'incomingTransfers', 'salesTrend'));
    }
}

