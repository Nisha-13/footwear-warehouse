@extends('layouts.app')
@section('title', $warehouse->name)
@section('subtitle', $warehouse->code . ' · ' . ucfirst($warehouse->type) . ' warehouse')

@php
    $s = fn (...$p) => request()->routeIs(...$p) ? 'active' : '';
    $totalUnits = $topStock->sum('quantity');
@endphp

@section('content')
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-buildings me-2 text-primary"></i>Warehouse Profile</span>
                <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Owner</div><div class="fw-semibold">{{ $warehouse->owner_name ?? '—' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Manager</div><div class="fw-semibold">{{ $warehouse->manager?->name ?? 'Unassigned' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Phone</div><div class="fw-semibold">{{ $warehouse->phone ?? '—' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Parent</div><div class="fw-semibold">{{ $warehouse->parent?->name ?? '—' }}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Address</div><div class="fw-semibold">{{ $warehouse->address ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="row g-3">
            <div class="col-6"><x-stat label="Retail Shops" :value="$warehouse->shops->count()" icon="bi-shop" tone="success" /></div>
            <div class="col-6"><x-stat label="Top-line Units" :value="number_format($totalUnits)" icon="bi-boxes" tone="primary" /></div>
            <div class="col-6"><x-stat label="Capacity" :value="number_format((int) $warehouse->capacity)" icon="bi-arrows-expand" tone="info" /></div>
            <div class="col-6"><x-stat label="Status" :value="ucfirst($warehouse->status)" icon="bi-check-circle" tone="success" /></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-geo-alt me-2 text-danger"></i>Location</div>
            <div class="card-body p-2"><div id="warehouseMap" style="height:320px;border-radius:12px"></div></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-shop me-2 text-success"></i>Supplied Retail Shops</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Shop</th><th>Owner</th><th class="text-end">Units</th></tr></thead>
                    <tbody>
                    @forelse ($shops as $shop)
                        <tr>
                            <td><a href="{{ route('shops.show', $shop) }}" class="text-decoration-none fw-semibold">{{ $shop->display_name }}</a></td>
                            <td class="small text-muted">{{ $shop->owner_name }}</td>
                            <td class="text-end">{{ number_format((int) $shop->stock_units) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No shops assigned.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><i class="bi bi-box-seam me-2 text-primary"></i>Top Stock Items</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Product</th><th>Brand</th><th>Size</th><th>Color</th><th class="text-end">Qty</th></tr></thead>
            <tbody>
            @forelse ($topStock as $stock)
                <tr>
                    <td class="fw-semibold">{{ $stock->productVariant->product->name }}</td>
                    <td>{{ $stock->productVariant->product->brand?->name ?? '—' }}</td>
                    <td>{{ $stock->productVariant->size }}</td>
                    <td>{{ $stock->productVariant->color }}</td>
                    <td class="text-end fw-semibold">{{ number_format($stock->quantity) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No stock.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const lat = {{ $warehouse->latitude ?? 31.418715 }};
    const lng = {{ $warehouse->longitude ?? 73.079109 }};
    const map = L.map('warehouseMap').setView([lat, lng], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);
    L.marker([lat, lng]).addTo(map).bindPopup(@json($warehouse->name)).openPopup();
    @foreach ($warehouse->shops as $shop)
        @if ($shop->latitude && $shop->longitude)
        L.circleMarker([{{ $shop->latitude }}, {{ $shop->longitude }}], { radius: 7, color: '#10b981', fillOpacity: .7 }).addTo(map).bindPopup(@json($shop->display_name));
        @endif
    @endforeach
</script>
@endpush
