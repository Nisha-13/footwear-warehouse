<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WarehouseManagerScopeTest extends TestCase
{
    use DatabaseTransactions;

    protected function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    protected function manager(): User
    {
        return $this->user('lahore.manager@wms.test');
    }

    protected function ownShop(): Shop
    {
        return Shop::where('warehouse_id', $this->manager()->warehouse_id)->firstOrFail();
    }

    protected function foreignShop(): Shop
    {
        return Shop::where('warehouse_id', '!=', $this->manager()->warehouse_id)->firstOrFail();
    }

    /** Build a pending warehouse→shop transfer through the real flow. */
    protected function createTransfer(User $creator, Shop $shop, ?int $riderId = null): Transfer
    {
        $variant = ProductVariant::first();

        $this->actingAs($creator)->post(route('transfers.store'), [
            'direction' => 'warehouse_to_shop',
            'from_warehouse_id' => $shop->warehouse_id,
            'to_shop_id' => $shop->id,
            'rider_id' => $riderId,
            'payment_mode' => 'credit',
            'items' => [['product_variant_id' => $variant->id, 'quantity' => 2]],
        ])->assertRedirect();

        return Transfer::latest('id')->firstOrFail();
    }

    public function test_manager_transfer_form_only_offers_own_warehouse_and_shops(): void
    {
        $page = $this->actingAs($this->manager())->get(route('transfers.create'))->assertOk();

        $page->assertSee($this->ownShop()->display_name);
        $page->assertDontSee($this->foreignShop()->display_name);

        $page->assertViewHas('sourceWarehouses', fn ($w) => $w->count() === 1 && (int) $w->first()->id === (int) $this->manager()->warehouse_id);
    }

    public function test_manager_cannot_create_transfer_from_foreign_warehouse(): void
    {
        $foreignWarehouseId = Warehouse::where('id', '!=', $this->manager()->warehouse_id)->value('id');
        $before = Transfer::count();

        $this->actingAs($this->manager())->post(route('transfers.store'), [
            'direction' => 'warehouse_to_shop',
            'from_warehouse_id' => $foreignWarehouseId,
            'to_shop_id' => $this->ownShop()->id,
            'payment_mode' => 'credit',
            'items' => [['product_variant_id' => ProductVariant::first()->id, 'quantity' => 2]],
        ])->assertSessionHasErrors('from_warehouse_id');

        $this->assertSame($before, Transfer::count());
    }

    public function test_manager_cannot_transfer_to_foreign_shop(): void
    {
        $before = Transfer::count();

        $this->actingAs($this->manager())->post(route('transfers.store'), [
            'direction' => 'warehouse_to_shop',
            'from_warehouse_id' => $this->manager()->warehouse_id,
            'to_shop_id' => $this->foreignShop()->id,
            'payment_mode' => 'credit',
            'items' => [['product_variant_id' => ProductVariant::first()->id, 'quantity' => 2]],
        ])->assertSessionHasErrors('to_shop_id');

        $this->assertSame($before, Transfer::count());
    }

    public function test_manager_can_create_transfer_to_own_shop(): void
    {
        $transfer = $this->createTransfer($this->manager(), $this->ownShop());

        $this->assertSame($this->manager()->warehouse_id, $transfer->from_warehouse_id);
        $this->assertSame($this->ownShop()->id, $transfer->to_shop_id);
    }

    public function test_manager_cannot_view_or_manage_foreign_transfer(): void
    {
        $foreign = $this->createTransfer($this->user('admin@wms.test'), $this->foreignShop());

        $this->actingAs($this->manager())->get(route('transfers.show', $foreign))->assertForbidden();
        $this->actingAs($this->manager())->post(route('transfers.dispatch', $foreign))->assertForbidden();
        $this->actingAs($this->manager())->post(route('transfers.cancel', $foreign))->assertForbidden();
        $this->actingAs($this->manager())->post(route('transfers.assign', $foreign), [
            'rider_id' => User::where('role', User::ROLE_RIDER)->value('id'),
        ])->assertForbidden();
        $this->actingAs($this->manager())->delete(route('transfers.destroy', $foreign))->assertForbidden();
    }

    public function test_manager_can_manage_own_transfer(): void
    {
        $transfer = $this->createTransfer($this->manager(), $this->ownShop());

        $this->actingAs($this->manager())->get(route('transfers.show', $transfer))->assertOk();
        $this->actingAs($this->manager())->post(route('transfers.cancel', $transfer))->assertRedirect();

        $this->assertSame('cancelled', $transfer->fresh()->status);
    }

    public function test_manager_cannot_view_foreign_shop(): void
    {
        $this->actingAs($this->manager())->get(route('shops.show', $this->ownShop()))->assertOk();
        $this->actingAs($this->manager())->get(route('shops.show', $this->foreignShop()))->assertForbidden();
    }

    public function test_manager_report_is_hard_scoped_to_own_warehouse(): void
    {
        $foreignWarehouseId = Warehouse::where('id', '!=', $this->manager()->warehouse_id)->value('id');

        $this->actingAs($this->manager())
            ->get(route('reports.index', ['warehouse_id' => $foreignWarehouseId]))
            ->assertOk()
            ->assertViewHas('warehouseId', fn ($id) => (int) $id === (int) $this->manager()->warehouse_id)
            ->assertViewHas('warehouses', fn ($w) => $w->count() === 1);
    }

    public function test_manager_cannot_adjust_foreign_warehouse_stock(): void
    {
        $foreignStock = Stock::where('stockable_type', Warehouse::class)
            ->where('stockable_id', '!=', $this->manager()->warehouse_id)
            ->firstOrFail();

        $this->actingAs($this->manager())->post(route('inventory.adjust', $foreignStock), [
            'quantity' => 1,
        ])->assertForbidden();
    }

    public function test_manager_tracking_feed_excludes_foreign_deliveries(): void
    {
        $rider = User::where('role', User::ROLE_RIDER)->firstOrFail();
        $own = $this->createTransfer($this->user('admin@wms.test'), $this->ownShop(), (int) $rider->id);

        $foreignActiveIds = Delivery::whereNotIn('status', ['delivered', 'cancelled', 'failed'])
            ->whereHas('transfer', fn ($t) => $t->where('from_warehouse_id', '!=', $this->manager()->warehouse_id)
                ->where('to_shop_id', '!=', $this->ownShop()->id))
            ->pluck('id');

        $payload = $this->actingAs($this->manager())->get(route('tracking.positions'))->assertOk()->json();

        $ids = collect($payload['deliveries'])->pluck('id');

        $this->assertNotEmpty($ids);
        $this->assertTrue($ids->contains($own->deliveries()->first()->id));
        if ($foreignActiveIds->isNotEmpty()) {
            $this->assertEmpty($ids->intersect($foreignActiveIds->all()));
        }
    }

    public function test_manager_delivery_pages_are_scoped(): void
    {
        $rider = User::where('role', User::ROLE_RIDER)->firstOrFail();
        $own = $this->createTransfer($this->user('admin@wms.test'), $this->ownShop(), (int) $rider->id);

        $foreignDelivery = Delivery::whereNotIn('status', ['delivered', 'cancelled', 'failed'])
            ->whereHas('transfer', fn ($t) => $t->where('from_warehouse_id', '!=', $this->manager()->warehouse_id))
            ->first();

        $this->actingAs($this->manager())->get(route('deliveries.show', $own->deliveries()->first()))->assertOk();

        if ($foreignDelivery) {
            $this->actingAs($this->manager())->get(route('deliveries.show', $foreignDelivery))->assertForbidden();
        }
    }
}
