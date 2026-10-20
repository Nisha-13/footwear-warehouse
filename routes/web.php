<?php

use App\Http\Controllers\AgeGroupController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RiderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ShopSalesController;
use App\Http\Controllers\StyleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [AuthController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Master data - Super Admin only
    Route::middleware('role:super_admin')->group(function () {
        Route::resource('warehouses', WarehouseController::class);
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('suppliers', SupplierController::class);
        Route::resource('brands', BrandController::class);
        Route::resource('styles', StyleController::class);
        Route::resource('categories', CategoryController::class);
        Route::resource('age-groups', AgeGroupController::class);
        Route::resource('products', ProductController::class);
        Route::post('products/{product}/variants', [ProductController::class, 'addVariant'])->name('products.variants.store');
        Route::delete('variants/{variant}', [ProductController::class, 'destroyVariant'])->name('variants.destroy');

        Route::get('ledger', [LedgerController::class, 'index'])->name('ledger.index');
        Route::get('ledger/supplier/{supplier}', [LedgerController::class, 'supplier'])->name('ledger.supplier');

        Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
        Route::post('purchases/{purchase}/payment', [PurchaseController::class, 'addPayment'])->name('purchases.payment');
    });

    // Accountant-only finance access (beyond shared inventory movements)
    Route::get('purchases', [PurchaseController::class, 'index'])
        ->middleware('role:super_admin,accountant')->name('purchases.index');
    Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])
        ->middleware('role:super_admin,accountant')->name('purchases.show');
    Route::get('ledger', [LedgerController::class, 'index'])
        ->middleware('role:super_admin,accountant')->name('ledger.index');
    Route::get('ledger/supplier/{supplier}', [LedgerController::class, 'supplier'])
        ->middleware('role:super_admin,accountant')->name('ledger.supplier');

    // Inventory
    Route::get('inventory', [InventoryController::class, 'index'])
        ->middleware('role:super_admin,warehouse_manager')->name('inventory.index');
    Route::post('inventory/{stock}/adjust', [InventoryController::class, 'adjust'])
        ->middleware('role:super_admin,warehouse_manager')->name('inventory.adjust');
    Route::get('inventory/movements', [InventoryController::class, 'movements'])
        ->middleware('role:super_admin,warehouse_manager,shopkeeper,accountant')->name('inventory.movements');

    // Distribution & logistics
    Route::get('transfers', [TransferController::class, 'index'])
        ->middleware('role:super_admin,warehouse_manager,shopkeeper')->name('transfers.index');
    Route::get('transfers/create', [TransferController::class, 'create'])
        ->middleware('role:super_admin,warehouse_manager')->name('transfers.create');
    Route::post('transfers', [TransferController::class, 'store'])
        ->middleware('role:super_admin,warehouse_manager')->name('transfers.store');
    Route::get('transfers/{transfer}', [TransferController::class, 'show'])
        ->middleware('role:super_admin,warehouse_manager,shopkeeper')->name('transfers.show');
    Route::post('transfers/{transfer}/assign', [TransferController::class, 'assign'])
        ->middleware('role:super_admin,warehouse_manager')->name('transfers.assign');
    Route::post('transfers/{transfer}/dispatch', [TransferController::class, 'dispatch'])
        ->middleware('role:super_admin,warehouse_manager')->name('transfers.dispatch');
    Route::post('transfers/{transfer}/deliver', [TransferController::class, 'deliver'])
        ->middleware('role:super_admin,warehouse_manager,shopkeeper')->name('transfers.deliver');
    Route::post('transfers/{transfer}/cancel', [TransferController::class, 'cancel'])
        ->middleware('role:super_admin,warehouse_manager')->name('transfers.cancel');
    Route::delete('transfers/{transfer}', [TransferController::class, 'destroy'])
        ->middleware('role:super_admin,warehouse_manager')->name('transfers.destroy');

    Route::get('deliveries', [DeliveryController::class, 'index'])
        ->middleware('role:super_admin,warehouse_manager')->name('deliveries.index');
    Route::get('deliveries/{delivery}', [DeliveryController::class, 'show'])
        ->middleware('role:super_admin,warehouse_manager,rider')->name('deliveries.show');
    Route::post('deliveries/{delivery}/status', [DeliveryController::class, 'updateStatus'])
        ->middleware('role:super_admin,warehouse_manager,rider')->name('deliveries.status');
    Route::get('deliveries/{delivery}/position', [DeliveryController::class, 'position'])
        ->middleware('role:super_admin,warehouse_manager,rider')->name('deliveries.position');
    Route::post('location', [DeliveryController::class, 'storeLocation'])
        ->middleware('role:rider', 'throttle:30,1')->name('deliveries.location');

    Route::get('tracking', [TrackingController::class, 'index'])
        ->middleware('role:super_admin,warehouse_manager')->name('tracking.index');
    Route::get('tracking/positions', [TrackingController::class, 'positions'])
        ->middleware('role:super_admin,warehouse_manager')->name('tracking.positions');

    Route::get('reports', [ReportController::class, 'index'])
        ->middleware('role:super_admin,warehouse_manager,accountant')->name('reports.index');

    // Shops: resource (create/store/edit/update/destroy) first so
    // "shops/create" is not swallowed by the "shops/{shop}" show route.
    Route::resource('shops', ShopController::class)->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->middleware('role:super_admin');
    Route::get('shops', [ShopController::class, 'index'])
        ->middleware('role:super_admin,warehouse_manager')->name('shops.index');
    Route::get('shops/{shop}', [ShopController::class, 'show'])
        ->middleware('role:super_admin,warehouse_manager')->name('shops.show');

    // Shopkeeper
    Route::get('shop/inventory', [ShopSalesController::class, 'inventory'])
        ->middleware('role:shopkeeper')->name('shop.inventory');
    Route::get('shop/sales', [ShopSalesController::class, 'index'])
        ->middleware('role:shopkeeper')->name('shop.sales.index');
    Route::get('shop/sales/create', [ShopSalesController::class, 'create'])
        ->middleware('role:shopkeeper')->name('shop.sales.create');
    Route::post('shop/sales', [ShopSalesController::class, 'store'])
        ->middleware('role:shopkeeper')->name('shop.sales.store');
    Route::get('shop/sales/{sale}', [ShopSalesController::class, 'show'])
        ->middleware('role:shopkeeper')->name('shop.sales.show');

    // Rider
    Route::middleware('role:rider')->group(function () {
        Route::get('rider', [RiderController::class, 'dashboard'])->name('rider.dashboard');
        Route::get('rider/route', [RiderController::class, 'route'])->name('rider.route');
        Route::get('rider/deliveries', [RiderController::class, 'deliveries'])->name('rider.deliveries.index');
    });
});
