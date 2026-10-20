@extends('layouts.app')
@section('title', 'Rider Dashboard')
@section('subtitle', 'Your assigned deliveries and route')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><x-stat label="Assigned" :value="$stats['assigned']" icon="bi-inbox" tone="primary" /></div>
    <div class="col-6 col-lg-3"><x-stat label="In Transit" :value="$stats['in_transit']" icon="bi-truck" tone="warning" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Delivered" :value="$stats['delivered']" icon="bi-check-circle" tone="success" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Today" :value="$stats['today_delivered']" icon="bi-calendar-check" tone="info" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check me-2 text-primary"></i>Pending Stops (sequenced)</span>
                <a href="{{ route('rider.route') }}" class="btn btn-sm btn-primary"><i class="bi bi-map"></i> View Route</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($pending as $delivery)
                    <a href="{{ route('deliveries.show', $delivery) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold"><span class="badge bg-primary me-1">{{ $delivery->sequence ?: $loop->iteration }}</span>{{ $delivery->drop_name ?? $delivery->transfer?->toShop?->name ?? $delivery->transfer?->toWarehouse?->name }}</div>
                            <div class="small text-muted">{{ $delivery->delivery_no }} · {{ $delivery->drop_address }}</div>
                        </div>
                        <div class="text-end">
                            {!! transfer_status_badge($delivery->status) !!}
                            <div class="fw-semibold mt-1">{{ money($delivery->transaction_amount) }}</div>
                        </div>
                    </a>
                @empty
                    <div class="list-group-item text-muted text-center py-4">No pending deliveries. Great job!</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Activity</div>
            <div class="list-group list-group-flush">
                @forelse ($deliveries as $delivery)
                    <a href="{{ route('deliveries.show', $delivery) }}" class="list-group-item list-group-item-action d-flex justify-content-between">
                        <span>{{ $delivery->delivery_no }}</span>
                        {!! transfer_status_badge($delivery->status) !!}
                    </a>
                @empty
                    <div class="list-group-item text-muted">No deliveries yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@include('partials.live-tracking')
