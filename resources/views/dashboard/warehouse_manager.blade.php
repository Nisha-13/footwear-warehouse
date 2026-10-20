@extends('layouts.app')

@section('title', 'Warehouse Dashboard')
@section('subtitle', $warehouse->name . ' · ' . ucfirst($warehouse->city))

@section('content')
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><x-stat label="Stock Units" :value="number_format($stats['stock_units'])" icon="bi-boxes" tone="primary" :hint="$stats['skus'].' SKUs on hand'" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Retail Shops" :value="$stats['shops']" icon="bi-shop" tone="success" hint="Supplied by this hub" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Incoming Transfers" :value="$stats['incoming']" icon="bi-box-arrow-in-down" tone="info" hint="Pending receipt" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Outgoing Transfers" :value="$stats['outgoing']" icon="bi-box-arrow-up" tone="warning" hint="Pending dispatch" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Active Deliveries" :value="$stats['deliveries']" icon="bi-truck-front" tone="info" hint="Assigned to riders" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Low Stock Lines" :value="$stats['low_stock']" icon="bi-exclamation-triangle" tone="danger" hint="At or below reorder level" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Low Stock Alerts</span>
                <a href="{{ route('inventory.index', ['low_stock' => 1]) }}" class="small">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Product</th><th>Variant</th><th class="text-end">Qty</th><th class="text-end">Reorder</th></tr></thead>
                    <tbody>
                    @forelse ($lowStock as $stock)
                        <tr>
                            <td class="fw-semibold">{{ $stock->productVariant->product->name }}</td>
                            <td class="small text-muted">{{ $stock->productVariant->size }} / {{ $stock->productVariant->color }}</td>
                            <td class="text-end"><span class="badge badge-soft-danger">{{ $stock->quantity }}</span></td>
                            <td class="text-end text-muted">{{ $stock->reorder_level }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">All stock levels healthy.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-shop me-2 text-success"></i>Shop Stock Levels</span>
                <a href="{{ route('shops.index') }}" class="small">Manage</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Shop</th><th>Owner</th><th class="text-end">Units</th></tr></thead>
                    <tbody>
                    @foreach ($shops as $shop)
                        <tr>
                            <td><a href="{{ route('shops.show', $shop) }}" class="text-decoration-none fw-semibold">{{ $shop->display_name }}</a></td>
                            <td class="small text-muted">{{ $shop->owner_name }}</td>
                            <td class="text-end">{{ number_format((int) $shop->stock_units) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-arrow-left-right me-2 text-primary"></i>Recent Transfers</span>
        <a href="{{ route('transfers.index') }}" class="small">All transfers</a>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Transfer</th><th>Route</th><th>Rider</th><th>Status</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
            @forelse ($recentTransfers as $transfer)
                <tr>
                    <td><a href="{{ route('transfers.show', $transfer) }}" class="text-decoration-none">{{ $transfer->transfer_no }}</a></td>
                    <td class="small">{{ $transfer->fromWarehouse?->name }} → {{ $transfer->destination_name }}</td>
                    <td class="small">{{ $transfer->rider?->name ?? '—' }}</td>
                    <td>{!! status_badge($transfer->status) !!}</td>
                    <td class="text-end">{{ money($transfer->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No transfers recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
