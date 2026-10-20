@extends('layouts.app')

@section('title', 'Shop Dashboard')
@section('subtitle', $shop->display_name . ' · ' . $shop->city)

@section('content')
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><x-stat label="Stock Units" :value="number_format($stats['stock_units'])" icon="bi-boxes" tone="primary" :hint="$stats['skus'].' SKUs available'" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Sales Today" :value="money($stats['sales_today'])" icon="bi-cash-coin" tone="success" hint="Full cash & digital" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Sales This Month" :value="money($stats['sales_month'])" icon="bi-graph-up" tone="info" hint="Shop revenue" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Incoming Transfers" :value="$stats['incoming']" icon="bi-truck" tone="warning" hint="Expected restock" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up me-2 text-success"></i>Sales Trend — Last 14 Days</span>
                <a href="{{ route('shop.sales.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New Sale</a>
            </div>
            <div class="card-body"><canvas id="shopTrend" height="90"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Low Stock</div>
            <div class="list-group list-group-flush">
                @forelse ($lowStock as $stock)
                    <div class="list-group-item d-flex justify-content-between">
                        <div>
                            <div class="fw-semibold small">{{ $stock->productVariant->product->name }}</div>
                            <div class="text-muted" style="font-size:.72rem">{{ $stock->productVariant->size }} / {{ $stock->productVariant->color }}</div>
                        </div>
                        <span class="badge badge-soft-danger align-self-center">{{ $stock->quantity }} left</span>
                    </div>
                @empty
                    <div class="list-group-item text-muted small">Stock levels healthy.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-receipt me-2 text-primary"></i>Recent Sales</span>
                <a href="{{ route('shop.sales.index') }}" class="small">All</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Invoice</th><th>Customer</th><th>Mode</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @forelse ($recentSales as $sale)
                        <tr>
                            <td><a href="{{ route('shop.sales.show', $sale) }}" class="text-decoration-none">{{ $sale->invoice_no }}</a></td>
                            <td class="small">{{ $sale->customer_name ?? 'Walk-in' }}</td>
                            <td>{!! status_badge($sale->payment_mode) !!}</td>
                            <td class="text-end fw-semibold">{{ money($sale->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No sales recorded yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-truck me-2 text-info"></i>Incoming Transfers</span>
                <a href="{{ route('transfers.index') }}" class="small">All</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($incomingTransfers as $transfer)
                    <a href="{{ route('transfers.show', $transfer) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold small">{{ $transfer->transfer_no }}</div>
                            <div class="text-muted" style="font-size:.72rem">From {{ $transfer->fromWarehouse?->name }} · {{ $transfer->item_count }} pcs</div>
                        </div>
                        {!! status_badge($transfer->status) !!}
                    </a>
                @empty
                    <div class="list-group-item text-muted small">No transfers yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const trend = @json($salesTrend);
    new Chart(document.getElementById('shopTrend'), {
        type: 'bar',
        data: {
            labels: trend.map(t => t.day),
            datasets: [{ label: 'Sales (Rs)', data: trend.map(t => Number(t.total)), backgroundColor: '#10b981', borderRadius: 6 }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => 'Rs ' + v.toLocaleString() } } } }
    });
</script>
@endpush
