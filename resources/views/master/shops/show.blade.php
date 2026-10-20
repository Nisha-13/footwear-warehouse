@extends('layouts.app')
@section('title', $shop->display_name)
@section('subtitle', $shop->code . ' · ' . $shop->city)

@section('content')
<div class="row g-3 mb-3">
    <div class="col-lg-4"><x-stat label="Stock Units" :value="number_format($shop->stocks()->sum('quantity'))" icon="bi-boxes" tone="primary" hint="On-hand inventory" /></div>
    <div class="col-lg-4"><x-stat label="Sales (This Month)" :value="money($shop->sales()->whereMonth('sale_date', now()->month)->sum('total'))" icon="bi-cash-coin" tone="success" hint="Retail revenue" /></div>
    <div class="col-lg-4"><x-stat label="Credit Limit" :value="money($shop->credit_limit)" icon="bi-credit-card" tone="info" hint="Approved B2B credit" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-shop me-2 text-primary"></i>Shop Profile</span>
                @if (auth()->user()->isSuperAdmin())
                    <a href="{{ route('shops.edit', $shop) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                @endif
            </div>
            <div class="card-body">
                <div class="mb-2"><span class="text-muted small">Business</span><div class="fw-semibold">{{ $shop->business_name ?? $shop->name }}</div></div>
                <div class="mb-2"><span class="text-muted small">Owner</span><div class="fw-semibold">{{ $shop->owner_name }}</div></div>
                <div class="mb-2"><span class="text-muted small">Shopkeeper</span><div class="fw-semibold">{{ $shop->shopkeeper?->name ?? 'Unassigned' }}</div></div>
                <div class="mb-2"><span class="text-muted small">Phone</span><div class="fw-semibold">{{ $shop->phone ?? '—' }}</div></div>
                <div class="mb-2"><span class="text-muted small">Address</span><div class="fw-semibold">{{ $shop->address ?? '—' }}</div></div>
                <div class="mb-2"><span class="text-muted small">Supplying warehouse</span><div class="fw-semibold">{{ $shop->warehouse?->name }}</div></div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-geo-alt me-2 text-danger"></i>Location</div>
            <div class="card-body p-2"><div id="shopMap" style="height:300px;border-radius:12px"></div></div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-box-seam me-2 text-primary"></i>Top Stock</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Product</th><th>Variant</th><th class="text-end">Qty</th></tr></thead>
                    <tbody>
                    @forelse ($topStock as $stock)
                        <tr>
                            <td class="fw-semibold small">{{ $stock->productVariant->product->name }}</td>
                            <td class="small text-muted">{{ $stock->productVariant->size }} / {{ $stock->productVariant->color }}</td>
                            <td class="text-end">{{ $stock->quantity }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No stock.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-receipt me-2 text-success"></i>Recent Sales</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Invoice</th><th>Date</th><th>Mode</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="fw-semibold small">{{ $sale->invoice_no }}</td>
                            <td class="small text-muted">{{ $sale->sale_date->format('d M Y') }}</td>
                            <td>{!! status_badge($sale->payment_mode) !!}</td>
                            <td class="text-end">{{ money($sale->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No sales.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const lat = {{ $shop->latitude ?? 31.5 }};
    const lng = {{ $shop->longitude ?? 74.3 }};
    const map = L.map('shopMap').setView([lat, lng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);
    L.marker([lat, lng]).addTo(map).bindPopup(@json($shop->display_name)).openPopup();
    @if ($shop->warehouse && $shop->warehouse->latitude)
    L.circleMarker([{{ $shop->warehouse->latitude }}, {{ $shop->warehouse->longitude }}], { radius: 8, color: '#4f46e5', fillOpacity: .6 }).addTo(map).bindPopup(@json($shop->warehouse->name));
    @endif
</script>
@endpush
