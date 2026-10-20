<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\RiderLocation;
use App\Models\Shop;
use App\Models\User;
use App\Support\GeoService;

class TrackingController extends Controller
{
    public function index()
    {
        $isManager = auth()->user()->isWarehouseManager() && auth()->user()->warehouse_id;
        $scope = fn ($q) => $isManager ? $this->scopeToUsersWarehouse($q) : $q;

        $riders = User::where('role', User::ROLE_RIDER)
            ->with(['deliveries' => $scope])
            ->when($isManager, fn ($q) => $q->whereHas('deliveries', $scope))
            ->orderBy('name')->get();

        $activeDeliveries = $this->activeDeliveries()->get();
        $locations = RiderLocation::latestPerRider($riders->pluck('id')->all());

        return view('logistics.tracking.index', compact('riders', 'activeDeliveries', 'locations'));
    }

    /** Lightweight JSON feed polled by the live tracking map. */
    public function positions()
    {
        $deliveries = $this->activeDeliveries()->get();

        $riderIds = $deliveries->pluck('rider_id')->filter()->unique()->values()->all();
        $lastPings = RiderLocation::latestPerRider($riderIds);
        $speeds = RiderLocation::recentSpeedsByRider($riderIds);

        $payload = $deliveries->map(function (Delivery $delivery) use ($lastPings, $speeds) {
            $lastPing = $delivery->rider_id ? ($lastPings->get((int) $delivery->rider_id) ?? null) : null;

            $eta = GeoService::estimateEta(
                $lastPing?->latitude,
                $lastPing?->longitude,
                $delivery->drop_latitude,
                $delivery->drop_longitude,
                $lastPing ? ($speeds->get((int) $delivery->rider_id) ?? null) : null,
                $lastPing?->created_at,
            );

            return [
                'id' => $delivery->id,
                'no' => $delivery->delivery_no,
                'status' => $delivery->status,
                'rider' => $delivery->rider ? ['id' => $delivery->rider->id, 'name' => $delivery->rider->name] : null,
                'drop' => [
                    'lat' => $delivery->drop_latitude,
                    'lng' => $delivery->drop_longitude,
                    'name' => $delivery->drop_name,
                ],
                'rider_pos' => $lastPing ? ['lat' => $lastPing->latitude, 'lng' => $lastPing->longitude] : null,
                'eta_minutes' => $eta['eta_minutes'],
                'distance_km' => $eta['distance_km'],
                'speed_kmh' => $eta['speed_kmh'],
                'stale' => $eta['stale'],
                'last_seen' => $eta['last_seen'],
            ];
        })->values();

        $riderCounts = $deliveries->pluck('rider_id')->filter()->countBy()
            ->mapWithKeys(fn ($count, $riderId) => [(int) $riderId => $count]);

        return response()->json([
            'deliveries' => $payload,
            'rider_counts' => $riderCounts,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function activeDeliveries()
    {
        return $this->scopeToUsersWarehouse(
            Delivery::with(['rider', 'transfer.fromWarehouse', 'transfer.toShop', 'transfer.toWarehouse'])
                ->whereNotIn('status', ['delivered', 'cancelled', 'failed'])
                ->orderBy('sequence')
        );
    }

    /** Warehouse managers only see deliveries belonging to their own warehouse or its shops. */
    private function scopeToUsersWarehouse($query)
    {
        $user = auth()->user();

        if ($user->isWarehouseManager() && $user->warehouse_id) {
            $shopIds = Shop::where('warehouse_id', $user->warehouse_id)->pluck('id');
            $query->whereHas('transfer', fn ($t) => $t
                ->where('from_warehouse_id', $user->warehouse_id)
                ->orWhere('to_warehouse_id', $user->warehouse_id)
                ->orWhereIn('to_shop_id', $shopIds));
        }

        return $query;
    }
}
