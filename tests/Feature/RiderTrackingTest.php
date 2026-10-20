<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\ProductVariant;
use App\Models\RiderLocation;
use App\Models\Shop;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RiderTrackingTest extends TestCase
{
    use DatabaseTransactions;

    protected function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    /** Create a fresh assigned delivery for a rider via the real transfer flow. */
    protected function createDeliveryFor(?User $rider = null): array
    {
        $admin = $this->user('admin@wms.test');
        $variant = ProductVariant::first();
        $shop = Shop::first();
        $rider ??= User::where('role', User::ROLE_RIDER)
            ->where('warehouse_id', $shop->warehouse_id)->firstOrFail();

        $this->actingAs($admin)->post(route('transfers.store'), [
            'direction' => 'warehouse_to_shop',
            'from_warehouse_id' => $shop->warehouse_id,
            'to_shop_id' => $shop->id,
            'rider_id' => $rider->id,
            'payment_mode' => 'credit',
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $transfer = Transfer::latest('id')->firstOrFail();
        $delivery = Delivery::where('transfer_id', $transfer->id)
            ->where('rider_id', $rider->id)
            ->firstOrFail();

        return [$rider, $delivery];
    }

    /** The 5s server-side dedupe must never interfere with a test's first ping. */
    protected function clearRecentPings(User $rider): void
    {
        RiderLocation::where('rider_id', $rider->id)
            ->where('created_at', '>=', now()->subSeconds(5))
            ->delete();
    }

    protected function ping(User $rider, array $data = [])
    {
        return $this->actingAs($rider)->postJson(route('deliveries.location'), array_merge([
            'latitude' => 31.5000,
            'longitude' => 74.3000,
            'speed' => 31.5,
            'label' => 'live',
        ], $data));
    }

    public function test_admin_and_manager_can_view_tracking_page(): void
    {
        $this->actingAs($this->user('admin@wms.test'))->get(route('tracking.index'))->assertOk();

        $this->actingAs($this->user('lahore.manager@wms.test'))->get(route('tracking.index'))->assertOk();
    }

    public function test_tracking_page_renders_live_map_script(): void
    {
        $this->actingAs($this->user('admin@wms.test'))->get(route('tracking.index'))
            ->assertOk()
            ->assertSee('positionsUrl', false)
            ->assertSee('POLL_MS = 5000', false)
            ->assertSee('setInterval(refresh, POLL_MS)', false);
    }

    public function test_rider_cannot_access_tracking(): void
    {
        $rider = $this->user('rider1@wms.test');

        $this->actingAs($rider)->get(route('tracking.index'))->assertForbidden();
        $this->actingAs($rider)->get(route('tracking.positions'))->assertForbidden();
    }

    public function test_shopkeeper_cannot_access_tracking(): void
    {
        $shopkeeper = $this->user('gulberg.shop@wms.test');

        $this->actingAs($shopkeeper)->get(route('tracking.index'))->assertForbidden();
        $this->actingAs($shopkeeper)->get(route('tracking.positions'))->assertForbidden();
    }

    public function test_positions_returns_fleet_payload(): void
    {
        $response = $this->actingAs($this->user('admin@wms.test'))
            ->get(route('tracking.positions'))
            ->assertOk()
            ->assertJsonStructure([
                'deliveries',
                'rider_counts',
                'server_time',
            ]);

        $delivery = $response->json('deliveries.0');
        if ($delivery !== null) {
            $this->assertArrayHasKey('rider_pos', $delivery);
            $this->assertArrayHasKey('eta_minutes', $delivery);
            $this->assertArrayHasKey('speed_kmh', $delivery);
            $this->assertArrayHasKey('stale', $delivery);
        }
    }

    public function test_rider_can_post_location_ping(): void
    {
        [$rider] = $this->createDeliveryFor();
        $this->clearRecentPings($rider);

        $this->ping($rider)->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('rider_locations', [
            'rider_id' => $rider->id,
            'latitude' => 31.5000,
            'longitude' => 74.3000,
            'speed' => 31.5,
            'label' => 'live',
        ]);
    }

    public function test_location_rejects_non_rider_users(): void
    {
        $before = RiderLocation::max('id');

        $this->ping($this->user('gulberg.shop@wms.test'))->assertForbidden();

        $this->ping($this->user('admin@wms.test'))->assertForbidden();

        $this->assertSame($before, RiderLocation::max('id'));
    }

    public function test_location_rejects_out_of_range_coordinates(): void
    {
        [$rider] = $this->createDeliveryFor();
        $this->clearRecentPings($rider);
        $before = RiderLocation::max('id');

        $this->ping($rider, ['latitude' => 999])->assertJsonValidationErrors('latitude');
        $this->ping($rider, ['longitude' => -200])->assertJsonValidationErrors('longitude');

        $this->assertSame($before, RiderLocation::max('id'));
    }

    public function test_location_pings_are_deduplicated_within_five_seconds(): void
    {
        [$rider] = $this->createDeliveryFor();
        $this->clearRecentPings($rider);

        $this->ping($rider)->assertOk()->assertJson(['ok' => true])->assertJsonMissing(['throttled' => true]);

        $this->ping($rider)->assertOk()->assertJson(['throttled' => true]);

        $this->assertSame(1, RiderLocation::where('rider_id', $rider->id)
            ->where('created_at', '>=', now()->subSeconds(5))
            ->count());
    }

    public function test_foreign_delivery_id_is_ignored(): void
    {
        [$riderA] = $this->createDeliveryFor();
        $riderB = User::where('role', User::ROLE_RIDER)
            ->where('warehouse_id', $riderA->warehouse_id)
            ->where('id', '!=', $riderA->id)->firstOrFail();
        [, $foreign] = $this->createDeliveryFor($riderB);
        $this->clearRecentPings($riderA);

        $this->ping($riderA, ['delivery_id' => $foreign->id])->assertOk();

        $stored = RiderLocation::where('rider_id', $riderA->id)
            ->where('created_at', '>=', now()->subSeconds(5))
            ->firstOrFail();

        $this->assertNotSame($foreign->id, $stored->delivery_id);
    }

    public function test_ping_falls_back_to_own_in_progress_delivery(): void
    {
        [$rider, $delivery] = $this->createDeliveryFor();

        $this->actingAs($rider)->post(route('deliveries.status', $delivery), ['status' => 'dispatched'])
            ->assertRedirect();

        $this->clearRecentPings($rider);
        $this->ping($rider)->assertOk();

        $this->assertDatabaseHas('rider_locations', [
            'rider_id' => $rider->id,
            'delivery_id' => $delivery->id,
        ]);
    }

    public function test_rider_cannot_read_another_riders_delivery_position(): void
    {
        $riderOne = $this->user('rider1@wms.test');
        $riderTwo = $this->user('rider2@wms.test');
        $delivery = Delivery::where('rider_id', $riderOne->id)->firstOrFail();

        $this->actingAs($riderTwo)->get(route('deliveries.position', $delivery))->assertForbidden();
    }

    public function test_delivery_position_returns_eta_payload(): void
    {
        $rider = $this->user('rider1@wms.test');
        $delivery = Delivery::where('rider_id', $rider->id)->firstOrFail();

        $this->actingAs($rider)->get(route('deliveries.position', $delivery))
            ->assertOk()
            ->assertJsonStructure([
                'id', 'no', 'status', 'terminal', 'rider', 'drop',
                'rider_pos', 'eta_minutes', 'distance_km', 'speed_kmh', 'stale', 'last_seen', 'server_time',
            ]);
    }

    public function test_delivery_show_page_contains_live_eta_poller(): void
    {
        [$rider, $delivery] = $this->createDeliveryFor();

        $this->actingAs($this->user('admin@wms.test'))->get(route('deliveries.show', $delivery))
            ->assertOk()
            ->assertSee('startLiveEtaPolling', false)
            ->assertSee('liveEtaCard', false);
    }

    public function test_own_delivery_page_ships_live_tracker_to_rider(): void
    {
        [$rider, $delivery] = $this->createDeliveryFor();

        $this->actingAs($rider)->get(route('deliveries.show', $delivery))
            ->assertOk()
            ->assertSee('wmsLiveTracker', false)
            ->assertSee('watchPosition', false);
    }

    public function test_rider_pages_ship_live_tracker(): void
    {
        $rider = $this->user('rider1@wms.test');

        $this->actingAs($rider)->get(route('rider.route'))
            ->assertOk()
            ->assertSee('wmsLiveTracker', false);

        $this->actingAs($rider)->get(route('rider.dashboard'))
            ->assertOk()
            ->assertSee('wmsLiveTracker', false);
    }
}
