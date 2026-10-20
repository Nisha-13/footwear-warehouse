<?php

namespace App\Support;

use App\Models\Delivery;
use App\Models\DeliveryStatusHistory;
use App\Models\Transfer;
use App\Models\TransferItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransferService
{
    public function __construct(protected StockService $stockService) {}

    public function syncDelivery(Transfer $transfer, ?int $riderId): ?Delivery
    {
        if (! $riderId) {
            return null;
        }

        $shop = $transfer->toShop;
        $warehouse = $transfer->toWarehouse;

        $delivery = Delivery::updateOrCreate(
            ['transfer_id' => $transfer->id, 'rider_id' => $riderId],
            [
                'delivery_no' => $transfer->deliveries()->where('rider_id', $riderId)->value('delivery_no') ?? NumberGenerator::delivery(),
                'assigned_by' => auth()->id(),
                'type' => 'outward',
                'status' => 'assigned',
                'drop_name' => $shop?->display_name ?? $warehouse?->name,
                'drop_address' => $shop?->address ?? $warehouse?->address,
                'drop_latitude' => $shop?->latitude ?? $warehouse?->latitude,
                'drop_longitude' => $shop?->longitude ?? $warehouse?->longitude,
                'transaction_amount' => $transfer->total,
                'payment_mode' => $transfer->payment_mode,
                'assigned_at' => now(),
            ]
        );

        if ($delivery->wasRecentlyCreated) {
            $this->logStatus($delivery, 'assigned', 'Delivery assigned to rider.');
        }

        return $delivery;
    }

    public function dispatch(Transfer $transfer): void
    {
        if (! in_array($transfer->status, ['pending', 'assigned', 'draft'], true)) {
            throw new RuntimeException('This transfer cannot be dispatched from its current status.');
        }

        DB::transaction(function () use ($transfer) {
            $source = $transfer->fromWarehouse;

            if (! $source) {
                throw new RuntimeException('Source warehouse is missing.');
            }

            foreach ($transfer->items as $item) {
                $this->stockService->remove($item->productVariant, $source, $item->quantity, 'transfer_out', [
                    'reference_type' => Transfer::class,
                    'reference_id' => $transfer->id,
                    'note' => 'Dispatched via transfer ' . $transfer->transfer_no,
                ]);
            }

            $transfer->update(['status' => 'dispatched', 'dispatched_at' => now()]);

            foreach ($transfer->deliveries as $delivery) {
                if (in_array($delivery->status, ['assigned', 'picked_up'], true)) {
                    $delivery->update(['status' => 'dispatched', 'dispatched_at' => now()]);
                    $this->logStatus($delivery, 'dispatched', 'Transfer dispatched from source.');
                }
            }
        });
    }

    public function markInTransit(Transfer $transfer): void
    {
        if ($transfer->status === 'dispatched') {
            $transfer->update(['status' => 'in_transit']);
        }
    }

    public function deliver(Transfer $transfer): void
    {
        if (in_array($transfer->status, ['delivered', 'cancelled'], true)) {
            throw new RuntimeException('This transfer is already finalized.');
        }

        DB::transaction(function () use ($transfer) {
            $destination = $transfer->toShop ?? $transfer->toWarehouse;

            if (! $destination) {
                throw new RuntimeException('Destination is missing.');
            }

            foreach ($transfer->items as $item) {
                $this->stockService->add($item->productVariant, $destination, $item->quantity, 'transfer_in', [
                    'reference_type' => Transfer::class,
                    'reference_id' => $transfer->id,
                    'note' => 'Received via transfer ' . $transfer->transfer_no,
                ]);

                $item->update(['received_quantity' => $item->quantity]);
            }

            $transfer->update(['status' => 'delivered', 'delivered_at' => now()]);

            foreach ($transfer->deliveries as $delivery) {
                if ($delivery->status !== 'delivered') {
                    $delivery->update(['status' => 'delivered', 'delivered_at' => now()]);
                    $this->logStatus($delivery, 'delivered', 'Transfer received at destination.');
                }
            }
        });
    }

    public function cancel(Transfer $transfer): void
    {
        if ($transfer->status === 'delivered') {
            throw new RuntimeException('Delivered transfers cannot be cancelled.');
        }

        DB::transaction(function () use ($transfer) {
            if ($transfer->dispatched_at && ! $transfer->delivered_at) {
                $source = $transfer->fromWarehouse;
                foreach ($transfer->items as $item) {
                    $this->stockService->add($item->productVariant, $source, $item->quantity, 'transfer_cancelled', [
                        'reference_type' => Transfer::class,
                        'reference_id' => $transfer->id,
                        'note' => 'Transfer ' . $transfer->transfer_no . ' cancelled',
                    ]);
                }
            }

            $transfer->update(['status' => 'cancelled']);

            foreach ($transfer->deliveries as $delivery) {
                $delivery->update(['status' => 'cancelled']);
            }
        });
    }

    public function logStatus(Delivery $delivery, string $status, ?string $note = null): void
    {
        DeliveryStatusHistory::create([
            'delivery_id' => $delivery->id,
            'status' => $status,
            'latitude' => $delivery->drop_latitude,
            'longitude' => $delivery->drop_longitude,
            'note' => $note,
            'user_id' => auth()->id(),
        ]);
    }
}
