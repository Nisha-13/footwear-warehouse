<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RouteSmokeTest extends TestCase
{
    protected function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_super_admin_routes(): void
    {
        $user = $this->user('admin@wms.test');

        $routes = [
            'dashboard',
            'warehouses.index', 'shops.index', 'users.index', 'users.create',
            'suppliers.index', 'suppliers.create', 'brands.index', 'styles.index',
            'categories.index', 'age-groups.index', 'products.index', 'products.create',
            'inventory.index', 'inventory.movements', 'purchases.index', 'purchases.create',
            'ledger.index', 'transfers.index', 'transfers.create', 'deliveries.index',
            'tracking.index', 'reports.index', 'profile.show',
        ];

        foreach ($routes as $name) {
            $response = $this->actingAs($user)->get(route($name));
            $this->assertTrue(
                $response->isSuccessful() || $response->isRedirect(),
                "Route [{$name}] returned status {$response->status()}"
            );
        }
    }

    public function test_super_admin_detail_pages(): void
    {
        $user = $this->user('admin@wms.test');

        $urls = [
            route('products.show', \App\Models\Product::first()),
            route('products.edit', \App\Models\Product::first()),
            route('purchases.show', \App\Models\Purchase::first()),
            route('transfers.show', \App\Models\Transfer::first()),
            route('deliveries.show', \App\Models\Delivery::first()),
            route('suppliers.show', \App\Models\Supplier::first()),
            route('users.edit', \App\Models\User::where('role', 'shopkeeper')->first()),
            route('warehouses.show', \App\Models\Warehouse::first()),
            route('shops.show', \App\Models\Shop::first()),
            route('shops.create'),
            route('shops.edit', \App\Models\Shop::first()),
            route('warehouses.create'),
            route('warehouses.edit', \App\Models\Warehouse::first()),
            route('categories.show', \App\Models\Category::first()),
            route('brands.edit', \App\Models\Brand::first()),
            route('styles.edit', \App\Models\Style::first()),
            route('age-groups.edit', \App\Models\AgeGroup::first()),
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($user)->get($url);
            $this->assertTrue($response->isSuccessful() || $response->isRedirect(), "URL [{$url}] status {$response->status()}");
        }
    }

    public function test_shopkeeper_sale_detail(): void
    {
        $user = $this->user('gulberg.shop@wms.test');
        $sale = \App\Models\Sale::where('shop_id', $user->shop_id)->firstOrFail();

        $this->actingAs($user)->get(route('shop.sales.show', $sale))->assertSuccessful();
    }

    public function test_warehouse_manager_routes(): void
    {
        $user = $this->user('lahore.manager@wms.test');

        $routes = ['dashboard', 'inventory.index', 'transfers.index', 'transfers.create', 'deliveries.index', 'tracking.index', 'reports.index'];

        foreach ($routes as $name) {
            $response = $this->actingAs($user)->get(route($name));
            $this->assertTrue($response->isSuccessful() || $response->isRedirect(), "Route [{$name}] status {$response->status()}");
        }
    }

    public function test_shopkeeper_routes(): void
    {
        $user = $this->user('gulberg.shop@wms.test');

        $routes = ['dashboard', 'shop.inventory', 'shop.sales.index', 'shop.sales.create', 'transfers.index', 'inventory.movements'];

        foreach ($routes as $name) {
            $response = $this->actingAs($user)->get(route($name));
            $this->assertTrue($response->isSuccessful() || $response->isRedirect(), "Route [{$name}] status {$response->status()}");
        }
    }

    public function test_rider_routes(): void
    {
        $user = $this->user('rider1@wms.test');

        $routes = ['dashboard', 'rider.dashboard', 'rider.route', 'rider.deliveries.index'];

        foreach ($routes as $name) {
            $response = $this->actingAs($user)->get(route($name));
            $this->assertTrue($response->isSuccessful() || $response->isRedirect(), "Route [{$name}] status {$response->status()}");
        }
    }
}
