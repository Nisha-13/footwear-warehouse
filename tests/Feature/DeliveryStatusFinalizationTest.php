<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DeliveryStatusFinalizationTest extends TestCase
{
    use DatabaseTransactions;

    protected function createAssignedDelivery(): array
    {
        $admin = User::where('email', 'admin@wms.test')->firstOrFail();
        $variant = ProductVariant::first();
        $shop = Shop::first();
        $rider = User::where('role', User::ROLE_RIDER)->firstOrFail();

        $this->actingAs($admin)->post(route('transfers.store'), [
            'direction' => 'warehouse_to_shop',
            'from_warehouse_id' => $shop->warehouse_id,
            'to_shop_id' => $shop->id,
            'rider_id' => $rider->id,
            'payment_mode' => 'credit',
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 3],
            ],
        ])->assertRedirect();

        $transfer = Transfer::latest('id')->firstOrFail();
        $delivery = Delivery::where('transfer_id', $transfer->id)
            ->where('rider_id', $rider->id)
            ->firstOrFail();

        return [$rider, $delivery];
    }

    protected function advanceTo(User $rider, Delivery $delivery, string $status, array $extra = []): void
    {
        $this->actingAs($rider)
            ->post(route('deliveries.status', $delivery), array_merge(['status' => $status], $extra))
            ->assertRedirect();
    }

    public function test_rider_can_mark_delivery_delivered(): void
    {
        [$rider, $delivery] = $this->createAssignedDelivery();

        $this->actingAs($rider)
            ->post(route('deliveries.status', $delivery), ['status' => 'delivered'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $delivery->refresh();
        $this->assertSame('delivered', $delivery->status);
        $this->assertNotNull($delivery->delivered_at);
    }

    public function test_delivered_delivery_cannot_be_updated_again(): void
    {
        [$rider, $delivery] = $this->createAssignedDelivery();

        $this->advanceTo($rider, $delivery, 'delivered');

        $this->actingAs($rider)
            ->post(route('deliveries.status', $delivery), ['status' => 'in_transit'])
            ->assertSessionHas('error');

        $this->assertSame('delivered', $delivery->fresh()->status);

        $this->actingAs($rider)
            ->post(route('deliveries.status', $delivery), ['status' => 'cancelled', 'note' => 'attempt after delivery'])
            ->assertSessionHas('error');

        $this->assertSame('delivered', $delivery->fresh()->status);
    }

    public function test_backward_transition_is_rejected(): void
    {
        [$rider, $delivery] = $this->createAssignedDelivery();

        $this->advanceTo($rider, $delivery, 'dispatched');

        $this->actingAs($rider)
            ->post(route('deliveries.status', $delivery), ['status' => 'assigned'])
            ->assertSessionHasErrors('status');

        $this->assertSame('dispatched', $delivery->fresh()->status);
    }

    public function test_cannot_jump_back_from_in_transit(): void
    {
        [$rider, $delivery] = $this->createAssignedDelivery();

        $this->advanceTo($rider, $delivery, 'dispatched');
        $this->advanceTo($rider, $delivery, 'in_transit');

        $this->actingAs($rider)
            ->post(route('deliveries.status', $delivery), ['status' => 'picked_up'])
            ->assertSessionHasErrors('status');

        $this->assertSame('in_transit', $delivery->fresh()->status);
    }

    public function test_setting_the_same_status_is_rejected(): void
    {
        [$rider, $delivery] = $this->createAssignedDelivery();

        $this->actingAs($rider)
            ->post(route('deliveries.status', $delivery), ['status' => 'assigned'])
            ->assertSessionHasErrors('status');

        $this->assertSame('assigned', $delivery->fresh()->status);
    }

    public function test_cancel_requires_a_reason(): void
    {
        [$rider, $delivery] = $this->createAssignedDelivery();

        $this->actingAs($rider)
            ->post(route('deliveries.status', $delivery), ['status' => 'cancelled'])
            ->assertSessionHasErrors('note');

        $this->assertSame('assigned', $delivery->fresh()->status);
    }

    public function test_cancel_with_reason_is_recorded_and_final(): void
    {
        [$rider, $delivery] = $this->createAssignedDelivery();

        $this->actingAs($rider)->post(route('deliveries.status', $delivery), [
            'status' => 'cancelled',
            'note' => 'Customer refused the package',
        ])->assertRedirect()->assertSessionHas('success');

        $delivery->refresh();
        $this->assertSame('cancelled', $delivery->status);
        $this->assertSame('Customer refused the package', $delivery->failure_reason);
        $this->assertDatabaseHas('delivery_status_histories', [
            'delivery_id' => $delivery->id,
            'status' => 'cancelled',
            'note' => 'Cancelled — Customer refused the package',
        ]);

        $this->actingAs($rider)
            ->post(route('deliveries.status', $delivery), ['status' => 'picked_up'])
            ->assertSessionHas('error');

        $this->assertSame('cancelled', $delivery->fresh()->status);
    }

    public function test_failed_requires_a_structured_reason(): void
    {
        [$rider, $delivery] = $this->createAssignedDelivery();

        $this->actingAs($rider)
            ->post(route('deliveries.status', $delivery), ['status' => 'failed'])
            ->assertSessionHasErrors('failure_code');

        $this->actingAs($rider)
            ->post(route('deliveries.status', $delivery), ['status' => 'failed', 'failure_code' => 'not_a_real_code'])
            ->assertSessionHasErrors('failure_code');

        $this->assertSame('assigned', $delivery->fresh()->status);
    }

    public function test_failed_with_other_reason_requires_a_comment(): void
    {
        [$rider, $delivery] = $this->createAssignedDelivery();

        $this->actingAs($rider)->post(route('deliveries.status', $delivery), [
            'status' => 'failed',
            'failure_code' => 'other',
        ])->assertSessionHasErrors('note');

        $this->assertSame('assigned', $delivery->fresh()->status);
    }

    public function test_failed_records_reason_and_allows_retry(): void
    {
        [$rider, $delivery] = $this->createAssignedDelivery();

        $this->actingAs($rider)->post(route('deliveries.status', $delivery), [
            'status' => 'failed',
            'failure_code' => 'customer_unavailable',
            'note' => 'Gate was locked',
        ])->assertRedirect()->assertSessionHas('success');

        $delivery->refresh();
        $this->assertSame('failed', $delivery->status);
        $this->assertSame('customer_unavailable', $delivery->failure_reason);
        $this->assertSame('Customer not available', $delivery->failure_reason_label);
        $this->assertDatabaseHas('delivery_status_histories', [
            'delivery_id' => $delivery->id,
            'status' => 'failed',
            'note' => 'Customer not available — Gate was locked',
        ]);

        $this->assertNotSame('failed', $delivery->transfer->fresh()->status);

        $this->advanceTo($rider, $delivery, 'in_transit');
        $this->assertSame('in_transit', $delivery->fresh()->status);

        $this->advanceTo($rider, $delivery, 'delivered');
        $this->assertSame('delivered', $delivery->fresh()->status);
    }

    public function test_manager_cannot_update_finalized_delivery(): void
    {
        [$rider, $delivery] = $this->createAssignedDelivery();
        $admin = User::where('email', 'admin@wms.test')->firstOrFail();

        $this->advanceTo($rider, $delivery, 'delivered');

        $this->actingAs($admin)
            ->post(route('deliveries.status', $delivery), ['status' => 'assigned'])
            ->assertSessionHas('error');

        $this->assertSame('delivered', $delivery->fresh()->status);
    }

    public function test_update_form_offers_only_valid_next_statuses(): void
    {
        [$rider, $delivery] = $this->createAssignedDelivery();

        $this->actingAs($rider)->get(route('deliveries.show', $delivery))
            ->assertOk()
            ->assertSee('value="delivered"', false)
            ->assertSee('value="failed"', false)
            ->assertSee('value="cancelled"', false);

        $this->advanceTo($rider, $delivery, 'dispatched');

        $page = $this->actingAs($rider)->get(route('deliveries.show', $delivery));
        $page->assertOk()
            ->assertSee('value="in_transit"', false)
            ->assertSee('value="delivered"', false)
            ->assertDontSee('value="assigned"', false)
            ->assertDontSee('value="picked_up"', false)
            ->assertDontSee('value="dispatched"', false);
    }

    public function test_confirmation_prompt_is_rendered_on_status_form(): void
    {
        [$rider, $delivery] = $this->createAssignedDelivery();

        $this->actingAs($rider)->get(route('deliveries.show', $delivery))
            ->assertOk()
            ->assertSee('statusForm.addEventListener', false)
            ->assertSee('cannot be undone', false);
    }

    public function test_update_form_is_hidden_for_finalized_delivery(): void
    {
        [$rider, $delivery] = $this->createAssignedDelivery();

        $this->actingAs($rider)->get(route('deliveries.show', $delivery))
            ->assertOk()
            ->assertSee('Update Status', false);

        $this->advanceTo($rider, $delivery, 'cancelled', ['note' => 'Wrong address']);

        $this->actingAs($rider)->get(route('deliveries.show', $delivery))
            ->assertOk()
            ->assertSee('Status Finalized', false)
            ->assertSee('can no longer be updated', false)
            ->assertSee('Wrong address', false)
            ->assertDontSee('id="statusForm"', false);
    }
}
