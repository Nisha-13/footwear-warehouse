@extends('layouts.app')

@section('title', 'Executive Dashboard')
@section('subtitle', 'Faisalabad Main Branch & Regional Network Overview')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <x-stat label="Purchases (This Month)" :value="money($stats['purchases_month'])" icon="bi-cart-check" tone="primary" :hint="$stats['products'].' active SKUs'" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat label="Retail Sales (This Month)" :value="money($stats['sales_month'])" icon="bi-bag-check" tone="success" hint="Across all retail shops" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat label="Supplier Payable" :value="money($stats['receivable'])" icon="bi-journal-text" tone="warning" hint="Outstanding B2B balance" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat label="Total Stock Units" :value="number_format($stats['total_stock'])" icon="bi-boxes" tone="info" :hint="$stats['warehouses'].' warehouses · '.$stats['shops'].' shops'" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat label="Active Deliveries" :value="$stats['pending_deliveries']" icon="bi-truck-front" tone="info" :hint="$stats['riders'].' riders on field'" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat label="Transfers In Transit" :value="$stats['transfers_transit']" icon="bi-arrow-left-right" tone="primary" hint="Warehouse & shop replenishment" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat label="Regional Warehouses" :value="$warehouses->where('type', 'regional')->count()" icon="bi-buildings" tone="secondary" hint="Lahore · Islamabad · Karachi · Multan" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat label="Retail Shops" :value="$stats['shops']" icon="bi-shop" tone="success" hint="Local retail network" />
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Retail Sales — Last 14 Days</span>
                <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light">View reports</a>
            </div>
            <div class="card-body"><canvas id="salesTrend" height="90"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-bar-chart me-2 text-info"></i>Stock by Warehouse</div>
            <div class="card-body">
                @foreach ($warehouseStock as $warehouse)
                    @php $max = max(1, (int) $warehouseStock->max('stock_units')); @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="fw-semibold">{{ $warehouse->name }}</span>
                            <span class="text-muted">{{ number_format((int) $warehouse->stock_units) }} units</span>
                        </div>
                        <div class="progress" style="height:8px">
                            <div class="progress-bar" style="width: {{ ((int) $warehouse->stock_units / $max) * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-cart-check me-2 text-primary"></i>Recent Purchases</span>
                <a href="{{ route('purchases.index') }}" class="small">All</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($recentPurchases as $purchase)
                    <a href="{{ route('purchases.show', $purchase) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">{{ $purchase->invoice_no }}</span>
                            <span class="fw-semibold">{{ money($purchase->total) }}</span>
                        </div>
                        <div class="small text-muted">{{ $purchase->supplier->company_name }} · {{ $purchase->purchase_date->format('d M Y') }}</div>
                    </a>
                @empty
                    <div class="list-group-item text-muted">No purchases yet.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-arrow-left-right me-2 text-info"></i>Recent Transfers</span>
                <a href="{{ route('transfers.index') }}" class="small">All</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($recentTransfers as $transfer)
                    <a href="{{ route('transfers.show', $transfer) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">{{ $transfer->transfer_no }}</span>
                            {!! status_badge($transfer->status) !!}
                        </div>
                        <div class="small text-muted">{{ $transfer->fromWarehouse?->name }} → {{ $transfer->destination_name }}</div>
                    </a>
                @empty
                    <div class="list-group-item text-muted">No transfers yet.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-geo-alt me-2 text-danger"></i>Active Rider Jobs</span>
                <a href="{{ route('tracking.index') }}" class="small">Track</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($activeDeliveries as $delivery)
                    <a href="{{ route('deliveries.show', $delivery) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">{{ $delivery->drop_name }}</span>
                            {!! status_badge($delivery->status) !!}
                        </div>
                        <div class="small text-muted"><i class="bi bi-person-badge me-1"></i>{{ $delivery->rider?->name ?? 'Unassigned' }} · {{ $delivery->delivery_no }}</div>
                    </a>
                @empty
                    <div class="list-group-item text-muted">No active deliveries.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const trend = @json($salesTrend);
    const ctx = document.getElementById('salesTrend');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: trend.map(t => t.day),
            datasets: [{
                label: 'Sales (Rs)',
                data: trend.map(t => Number(t.total)),
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79,70,229,.12)',
                fill: true,
                tension: .35,
                borderWidth: 2,
                pointRadius: 3,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => 'Rs ' + v.toLocaleString() } } }
        }
    });
</script>
@endpush
