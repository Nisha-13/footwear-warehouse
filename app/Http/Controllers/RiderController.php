<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\User;

class RiderController extends Controller
{
    public function dashboard()
    {
        $rider = auth()->user();
        $deliveries = $rider->deliveries()->with(['transfer.fromWarehouse', 'transfer.toShop', 'transfer.toWarehouse'])
            ->latest()->take(20)->get();

        $stats = [
            'assigned' => $rider->deliveries()->where('status', 'assigned')->count(),
            'in_transit' => $rider->deliveries()->whereIn('status', ['dispatched', 'in_transit'])->count(),
            'delivered' => $rider->deliveries()->where('status', 'delivered')->count(),
            'today_delivered' => $rider->deliveries()->where('status', 'delivered')->whereDate('delivered_at', today())->count(),
        ];

        $pending = $rider->deliveries()
            ->with(['transfer.fromWarehouse', 'transfer.toShop', 'transfer.toWarehouse'])
            ->whereNotIn('status', ['delivered', 'cancelled', 'failed'])
            ->orderBy('sequence')->orderBy('id')
            ->take(10)->get();

        return view('rider.dashboard', compact('stats', 'deliveries', 'pending'));
    }

    public function route()
    {
        $rider = auth()->user();

        $stopsRaw = $rider->deliveries()
            ->with(['transfer.toShop', 'transfer.toWarehouse'])
            ->whereNotIn('status', ['delivered', 'cancelled', 'failed'])
            ->get()
            ->map(function ($d) {
                $drop = $d->transfer?->toShop ?? $d->transfer?->toWarehouse;
                return [
                    'id' => $d->id,
                    'name' => $d->drop_name ?: $drop?->name,
                    'address' => $d->drop_address ?: $drop?->address,
                    'latitude' => $d->drop_latitude ?? $drop?->latitude,
                    'longitude' => $d->drop_longitude ?? $drop?->longitude,
                    'delivery_no' => $d->delivery_no,
                    'status' => $d->status,
                    'payment_mode' => $d->payment_mode,
                    'amount' => $d->transaction_amount,
                ];
            })->toArray();

        $warehouse = $rider->warehouse;
        $origin = [$warehouse?->latitude, $warehouse?->longitude];

        $stops = $stopsRaw;
        $optimized = \App\Support\GeoService::optimiseRoute($stopsRaw, $warehouse?->latitude, $warehouse?->longitude);

        foreach ($optimized as $i => $stop) {
            Delivery::where('id', $stop['id'])->update(['sequence' => $i + 1]);
        }

        $activeDeliveryId = $rider->deliveries()
            ->whereIn('status', [
                Delivery::STATUS_PICKED_UP,
                Delivery::STATUS_DISPATCHED,
                Delivery::STATUS_IN_TRANSIT,
            ])
            ->orderByDesc('updated_at')
            ->value('id');

        return view('rider.route', compact('optimized', 'origin', 'warehouse', 'activeDeliveryId'));
    }

    public function deliveries()
    {
        $rider = auth()->user();
        $deliveries = $rider->deliveries()
            ->with(['transfer.fromWarehouse', 'transfer.toShop', 'transfer.toWarehouse'])
            ->latest()->paginate(20);

        return view('rider.deliveries', compact('deliveries'));
    }
}
