<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RiderWarehouseScopeTest extends TestCase
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

    protected function riderOf(int $warehouseId, ?int $exceptId = null): User
    {
        return User::where('role', User::ROLE_RIDER)
            ->where('status', 'active')
            ->where('warehouse_id', $warehouseId)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->firstOrFail();
    }

    protected function foreignRider(): User
    {
        return $this->riderOf(Warehouse::where('id', '!=', $this->manager()->warehouse_id)->value('id'));
    }

    protected function postTransfer(array $overrides = [])
    {
        return $this->actingAs($this->user('admin@wms.test'))->post(route('transfers.store'), array_merge([
            'direction' => 'warehouse_to_shop',
            'from_warehouse_id' => $this->manager()->warehouse_id,
            'to_shop_id' => $this->ownShop()->id,
            'payment_mode' => 'credit',
            'items' => [['product_variant_id' => ProductVariant::first()->id, 'quantity' => 2]],
        ], $overrides));
    }

    public function test_every_warehouse_has_a_pool_of_two_to_three_riders(): void
    {
        $warehouses = Warehouse::where('status', 'active')->get();
        $this->assertNotEmpty($warehouses);

        foreach ($warehouses as $warehouse) {
            $pool = User::where('role', User::ROLE_RIDER)
                ->where('status', 'active')
                ->where('warehouse_id', $warehouse->id)
                ->get();

            $this->assertGreaterThanOrEqual(2, $pool->count(), "{$warehouse->code} pool below 2 riders");
            $this->assertLessThanOrEqual(3, $pool->count(), "{$warehouse->code} pool above 3 riders");
        }

        $this->assertSame(0, User::where('role', User::ROLE_RIDER)
            ->where('status', 'active')
            ->whereNull('warehouse_id')
            ->count(), 'Active riders must belong to exactly one warehouse');
    }

    public function test_active_deliveries_are_ridden_by_local_riders(): void
    {
        $mismatches = Delivery::whereNotIn('status', ['delivered', 'cancelled', 'failed'])
            ->with(['rider', 'transfer'])
            ->get()
            ->filter(fn ($d) => $d->rider_id && $d->transfer
                && (int) $d->rider->warehouse_id !== (int) $d->transfer->from_warehouse_id);

        $this->assertCount(0, $mismatches, 'Active deliveries must be assigned to riders of the transfer source warehouse');
    }

    public function test_transfer_form_only_lists_own_pool_for_manager(): void
    {
        $page = $this->actingAs($this->manager())->get(route('transfers.create'))->assertOk();

        $ownRider = $this->riderOf($this->manager()->warehouse_id);
        $foreign = $this->foreignRider();

        $page->assertSee($ownRider->name);
        $page->assertDontSee($foreign->name);
    }

    public function test_transfer_form_riders_are_tagged_for_warehouse_filtering(): void
    {
        $warehouseId = $this->manager()->warehouse_id;

        $this->actingAs($this->user('admin@wms.test'))->get(route('transfers.create'))
            ->assertOk()
            ->assertSee('data-warehouse="' . $warehouseId . '"', false);
    }

    public function test_transfer_show_rider_list_limited_to_transfer_warehouse(): void
    {
        $transfer = Transfer::create([
            'transfer_no' => 'DLV-TEST-' . uniqid(),
            'from_warehouse_id' => $this->manager()->warehouse_id,
            'to_shop_id' => $this->ownShop()->id,
            'direction' => 'warehouse_to_shop',
            'status' => 'pending',
            'item_count' => 0,
            'subtotal' => 0,
            'shipping_cost' => 0,
            'total' => 0,
            'payment_mode' => 'credit',
        ]);

        $page = $this->actingAs($this->user('admin@wms.test'))->get(route('transfers.show', $transfer))->assertOk();

        $page->assertSee($this->riderOf($this->manager()->warehouse_id)->name);
        $page->assertDontSee($this->foreignRider()->name);
    }

    public function test_store_rejects_rider_from_another_warehouse(): void
    {
        $foreign = $this->foreignRider();
        $before = Transfer::count();

        $this->postTransfer(['rider_id' => $foreign->id])->assertSessionHasErrors('rider_id');

        $this->assertSame($before, Transfer::count());
    }

    public function test_store_accepts_rider_from_own_warehouse(): void
    {
        $own = $this->riderOf($this->manager()->warehouse_id);

        $this->postTransfer(['rider_id' => $own->id])->assertRedirect();

        $this->assertSame($own->id, Transfer::latest('id')->firstOrFail()->rider_id);
    }

    public function test_assign_rejects_foreign_rider_and_accepts_local_rider(): void
    {
        $this->postTransfer()->assertRedirect();
        $transfer = Transfer::latest('id')->firstOrFail();

        $this->actingAs($this->user('admin@wms.test'))
            ->post(route('transfers.assign', $transfer), ['rider_id' => $this->foreignRider()->id])
            ->assertSessionHasErrors('rider_id');

        $local = $this->riderOf($this->manager()->warehouse_id);

        $this->actingAs($this->user('admin@wms.test'))
            ->post(route('transfers.assign', $transfer), ['rider_id' => $local->id])
            ->assertRedirect();

        $this->assertSame($local->id, $transfer->fresh()->rider_id);
    }

    public function test_rider_cannot_be_assigned_across_cities(): void
    {
        $crossCityShop = Shop::create([
            'warehouse_id' => $this->manager()->warehouse_id,
            'name' => 'Cross City Test Shop',
            'business_name' => 'Cross City Test Shop',
            'code' => 'cross-city-' . uniqid(),
            'owner_name' => 'Test Owner',
            'city' => 'Karachi',
            'address' => 'Test address',
            'status' => 'active',
        ]);

        $local = $this->riderOf($this->manager()->warehouse_id);
        $before = Transfer::count();

        $this->postTransfer(['to_shop_id' => $crossCityShop->id, 'rider_id' => $local->id])
            ->assertSessionHasErrors('rider_id');

        $this->assertSame($before, Transfer::count());
    }

    public function test_riders_are_not_shared_between_warehouses(): void
    {
        $owners = User::where('role', User::ROLE_RIDER)
            ->where('status', 'active')
            ->pluck('warehouse_id', 'id');

        $this->assertTrue($owners->every(fn ($w) => $w !== null), 'Every active rider must belong to exactly one warehouse');
    }
}
