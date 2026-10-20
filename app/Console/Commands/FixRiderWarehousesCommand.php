<?php

namespace App\Console\Commands;

use App\Models\Delivery;
use App\Models\RiderLocation;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Console\Command;

class FixRiderWarehousesCommand extends Command
{
    protected $signature = 'wms:fix-riders';

    protected $description = 'Ensure each warehouse has its own 2-3 rider pool, repair mismatched delivery assignments, and clear wrong-city GPS pings.';

    public function handle(): int
    {
        $this->removeUnreferencedWarehouses();
        $this->ensureRiderPools();
        $this->repairMismatchedDeliveries();
        $this->clearWrongCityPings();

        $this->info('Done.');
        return self::SUCCESS;
    }

    protected function ensureRiderPools(): void
    {
        $warehouses = Warehouse::where('status', 'active')->get();

        foreach ($warehouses as $warehouse) {
            $count = User::where('role', User::ROLE_RIDER)
                ->where('status', 'active')
                ->where('warehouse_id', $warehouse->id)
                ->count();

            for ($n = $count + 1; $n <= 2; $n++) {
                $slug = strtolower(str_replace(['_', ' '], '-', $warehouse->code));
                User::create([
                    'name' => "Rider {$warehouse->city} {$n}",
                    'email' => "rider-{$slug}-{$n}@wms.test",
                    'password' => bcrypt('password'),
                    'role' => User::ROLE_RIDER,
                    'phone' => '+92 3' . rand(10, 45) . ' ' . rand(1000000, 9999999),
                    'designation' => 'Delivery Rider',
                    'warehouse_id' => $warehouse->id,
                    'status' => 'active',
                ]);
                $this->info("Created rider rider-{$slug}-{$n}@wms.test for {$warehouse->code}");
            }

            if ($count > 3) {
                $this->warn("{$warehouse->code} has {$count} active riders (expected 2-3). Extras left in place.");
            }
        }
    }

    /** Delete warehouses that no data references (leftover manual test rows). */
    protected function removeUnreferencedWarehouses(): void
    {
        $candidates = Warehouse::query()
            ->where(fn ($q) => $q->where('status', '!=', 'active')->orWhere('name', 'not like', '%Warehouse%'))
            ->get();

        foreach ($candidates as $w) {
            $referenced = Shop::where('warehouse_id', $w->id)->exists()
                || Stock::where('stockable_type', Warehouse::class)->where('stockable_id', $w->id)->exists()
                || \App\Models\Purchase::where('warehouse_id', $w->id)->exists()
                || \App\Models\Transfer::where('from_warehouse_id', $w->id)->orWhere('to_warehouse_id', $w->id)->exists()
                || Warehouse::where('parent_id', $w->id)->exists()
                || User::where('warehouse_id', $w->id)->where('status', 'active')
                    ->where(fn ($q) => $q->where('role', '!=', User::ROLE_RIDER)->orWhereExists(function ($sub) use ($w) {
                        $sub->selectRaw('1')->from('deliveries')
                            ->join('transfers', 'transfers.id', '=', 'deliveries.transfer_id')
                            ->whereColumn('deliveries.rider_id', 'users.id')
                            ->where('transfers.from_warehouse_id', $w->id)
                            ->whereNotIn('deliveries.status', ['delivered', 'cancelled', 'failed']);
                    }))
                    ->exists();

            if ($referenced) {
                continue;
            }

            // Auto-created filler riders on an empty junk warehouse go with it.
            User::where('warehouse_id', $w->id)
                ->where('role', User::ROLE_RIDER)
                ->whereDoesntHave('deliveries', fn ($q) => $q->whereNotIn('status', ['delivered', 'cancelled', 'failed']))
                ->delete();

            $this->info("Deleted unreferenced warehouse {$w->code} ({$w->name}).");
            $w->delete();
        }
    }

    /** Reassign active deliveries whose rider belongs to a different warehouse than the transfer source. */
    protected function repairMismatchedDeliveries(): void
    {
        $active = Delivery::with(['rider', 'transfer'])
            ->whereNotIn('status', ['delivered', 'cancelled', 'failed'])
            ->get()
            ->filter(function (Delivery $d) {
                if (! $d->transfer || ! $d->transfer->from_warehouse_id) {
                    return false;
                }
                if (! $d->rider_id) {
                    return true;
                }

                return (int) $d->rider->warehouse_id !== (int) $d->transfer->from_warehouse_id;
            });

        foreach ($active as $delivery) {
            $fromWarehouseId = (int) $delivery->transfer->from_warehouse_id;

            $replacement = User::where('role', User::ROLE_RIDER)
                ->where('status', 'active')
                ->where('warehouse_id', $fromWarehouseId)
                ->get()
                ->sortBy(fn ($r) => Delivery::where('rider_id', $r->id)
                    ->whereNotIn('status', ['delivered', 'cancelled', 'failed'])
                    ->count())
                ->first();

            if (! $replacement) {
                $this->error("Delivery {$delivery->delivery_no}: no rider pool at warehouse {$fromWarehouseId}; cancelling transfer.");
                $delivery->transfer->update(['status' => 'cancelled', 'rider_id' => null]);
                $delivery->update(['status' => 'cancelled', 'rider_id' => null]);
                continue;
            }

            $delivery->transfer->update(['rider_id' => $replacement->id]);
            $delivery->update(['rider_id' => $replacement->id]);
            $this->info("Delivery {$delivery->delivery_no}: reassigned to {$replacement->email} (warehouse {$fromWarehouseId}).");
        }
    }

    /** Delete GPS pings that are more than 100 km from the rider's own warehouse. */
    protected function clearWrongCityPings(): void
    {
        $deleted = 0;

        foreach (User::where('role', User::ROLE_RIDER)->whereNotNull('warehouse_id')->with('warehouse')->get() as $rider) {
            $w = $rider->warehouse;
            if (! $w || $w->latitude === null || $w->longitude === null) {
                continue;
            }

            $wrongCity = RiderLocation::where('rider_id', $rider->id)->get()
                ->filter(fn (RiderLocation $loc) => $this->distanceKm(
                    (float) $loc->latitude, (float) $loc->longitude,
                    (float) $w->latitude, (float) $w->longitude,
                ) > 100);

            foreach ($wrongCity as $loc) {
                $loc->delete();
                $deleted++;
            }
        }

        $this->info("Deleted {$deleted} wrong-city GPS pings.");
    }

    protected function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
