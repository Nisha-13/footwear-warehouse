<?php

namespace Tests\Feature;

use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MutationsSmokeTest extends TestCase
{
    use DatabaseTransactions;

    protected function admin(): User
    {
        return User::where('email', 'admin@wms.test')->firstOrFail();
    }

    public function test_can_create_purchase(): void
    {
        $variant = ProductVariant::first();
        $response = $this->actingAs($this->admin())->post(route('purchases.store'), [
            'supplier_id' => Supplier::first()->id,
            'warehouse_id' => Warehouse::where('type', 'main')->first()->id,
            'purchase_date' => now()->toDateString(),
            'tax' => 100,
            'paid_amount' => 500,
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 5, 'unit_cost' => 1000],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchases', ['tax' => 100]);
    }

    public function test_can_create_transfer_and_assign_dispatch_deliver(): void
    {
        $admin = $this->admin();
        $variant = ProductVariant::first();
        $shop = Shop::first();
        $rider = User::where('role', User::ROLE_RIDER)->first();

        $response = $this->actingAs($admin)->post(route('transfers.store'), [
            'direction' => 'warehouse_to_shop',
            'from_warehouse_id' => $shop->warehouse_id,
            'to_shop_id' => $shop->id,
            'rider_id' => $rider->id,
            'payment_mode' => 'credit',
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 3],
            ],
        ]);
        $response->assertRedirect();

        $transfer = \App\Models\Transfer::latest('id')->first();
        $this->assertNotNull($transfer);

        $this->actingAs($admin)->post(route('transfers.dispatch', $transfer))->assertRedirect();
        $transfer->refresh();
        $this->assertContains($transfer->status, ['dispatched', 'in_transit']);

        $this->actingAs($admin)->post(route('transfers.deliver', $transfer))->assertRedirect();
        $this->assertSame('delivered', $transfer->fresh()->status);
    }

    public function test_shopkeeper_can_record_sale(): void
    {
        $shopkeeper = User::where('email', 'gulberg.shop@wms.test')->firstOrFail();
        $stock = Stock::where('stockable_type', Shop::class)
            ->where('stockable_id', $shopkeeper->shop_id)
            ->where('quantity', '>', 5)->firstOrFail();

        $response = $this->actingAs($shopkeeper)->post(route('shop.sales.store'), [
            'payment_mode' => 'full_cash',
            'items' => [
                ['product_variant_id' => $stock->product_variant_id, 'quantity' => 2, 'unit_price' => 1500],
            ],
        ]);

        $response->assertRedirect(route('shop.sales.index'));
    }

    public function test_can_adjust_inventory(): void
    {
        $stock = Stock::first();
        $this->actingAs($this->admin())
            ->post(route('inventory.adjust', $stock), ['quantity' => 999, 'note' => 'test count'])
            ->assertRedirect();

        $this->assertSame(999, (int) $stock->fresh()->quantity);
    }

    public function test_can_create_user(): void
    {
        $this->actingAs($this->admin())->post(route('users.store'), [
            'name' => 'Test Cashier',
            'email' => 'test.cashier.' . uniqid() . '@wms.test',
            'role' => User::ROLE_SHOPKEEPER,
            'status' => 'active',
            'password' => 'password',
        ])->assertRedirect(route('users.index'));
    }
}
