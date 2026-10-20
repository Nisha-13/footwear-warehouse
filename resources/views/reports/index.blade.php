@extends('layouts.app')
@section('title', 'Reports & Analytics')
@section('subtitle', 'Financial, sales, procurement and inventory insights')

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET">
            <div class="col-md-2">
                <label class="form-label small">Report type</label>
                <select name="type" class="form-select form-select-sm">
                    @foreach (['financial' => 'Financial', 'sales' => 'Sales', 'procurement' => 'Procurement', 'inventory' => 'Inventory'] as $key => $label)
                        <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Period</label>
                <select name="period" class="form-select form-select-sm">
                    @foreach (['daily' => 'Daily', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $key => $label)
                        <option value="{{ $key }}" @selected($period === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">From</label>
                <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small">To</label>
                <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Warehouse</label>
                <select name="warehouse_id" class="form-select form-select-sm">
                    <option value="">All warehouses</option>
                    @foreach ($warehouses as $w)
                        <option value="{{ $w->id }}" @selected($warehouseId == $w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel"></i> Apply</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><x-stat label="Sales Revenue" :value="money($financials['sales_total'])" icon="bi-graph-up-arrow" tone="success" :hint="$financials['sales_count'].' orders'" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Procurement" :value="money($financials['purchase_total'])" icon="bi-bag-check" tone="primary" :hint="$financials['purchase_count'].' invoices'" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Gross Margin" :value="money($financials['gross_margin'])" icon="bi-cash-coin" tone="info" :hint="'Receivable '.money($financials['receivable'])" /></div>
    <div class="col-6 col-lg-3"><x-stat label="Transfers" :value="$financials['transfers']" icon="bi-truck" tone="warning" :hint="$financials['delivered'].' delivered'" /></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-bar-chart me-2 text-primary"></i>Sales vs Procurement ({{ ucfirst($period) }})</div>
            <div class="card-body"><canvas id="trendChart" height="110"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-pie-chart me-2 text-primary"></i>Sales by Category</div>
            <div class="card-body"><canvas id="categoryChart" height="220"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-wallet2 me-2 text-primary"></i>Inventory Valuation</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Stock at cost</span><span class="fw-semibold">{{ money($stockValue) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Stock at retail</span><span class="fw-semibold">{{ money($retailValue) }}</span></div>
                <hr>
                <div class="d-flex justify-content-between fs-5 fw-bold"><span>Potential margin</span><span class="text-success">{{ money($retailValue - $stockValue) }}</span></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-buildings me-2 text-primary"></i>Stock by Warehouse</div>
            <div class="list-group list-group-flush" style="max-height:260px;overflow:auto">
                @forelse ($inventoryByWarehouse as $wh)
                    <div class="list-group-item d-flex justify-content-between">
                        <span>{{ $wh->name }}</span>
                        <span class="badge badge-soft-primary">{{ number_format((int) $wh->units) }} units</span>
                    </div>
                @empty
                    <div class="list-group-item text-muted">No data.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-shop me-2 text-primary"></i>Stock by Shop</div>
            <div class="list-group list-group-flush" style="max-height:260px;overflow:auto">
                @forelse ($inventoryByShop as $shop)
                    <div class="list-group-item d-flex justify-content-between">
                        <span>{{ $shop->name }}</span>
                        <span class="badge badge-soft-info">{{ number_format((int) $shop->units) }} units</span>
                    </div>
                @empty
                    <div class="list-group-item text-muted">No data.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-trophy me-2 text-primary"></i>Top Products by Stock Volume</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>#</th><th>Product</th><th class="text-end">Units in stock</th></tr></thead>
            <tbody>
            @forelse ($topProducts as $product)
                <tr><td>{{ $loop->iteration }}</td><td>{{ $product->name }}</td><td class="text-end fw-semibold">{{ number_format((int) $product->units) }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted py-4">No data.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@php
$salesJson = $salesSeries->map(fn ($s) => ['period' => $s->period, 'total' => (float) $s->total])->values()->all();
$purchaseJson = $purchaseSeries->map(fn ($p) => ['period' => $p->period, 'total' => (float) $p->total])->values()->all();
$categoryJson = $categorySales->map(fn ($c) => ['category' => $c->category ?? 'Uncategorised', 'total' => (float) $c->total])->values()->all();
@endphp

@push('scripts')
<script>
    const salesSeries = @json($salesJson);
    const purchaseSeries = @json($purchaseJson);
    const categorySales = @json($categoryJson);
    const money = n => 'Rs ' + Number(n).toLocaleString();

    const periods = [...new Set([...salesSeries.map(s => s.period), ...purchaseSeries.map(p => p.period)])].sort();
    const salesMap = Object.fromEntries(salesSeries.map(s => [s.period, s.total]));
    const purchaseMap = Object.fromEntries(purchaseSeries.map(p => [p.period, p.total]));

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: periods,
            datasets: [
                {label: 'Sales', data: periods.map(p => salesMap[p] || 0), borderColor: '#198754', backgroundColor: 'rgba(25,135,84,.12)', fill: true, tension: .3},
                {label: 'Procurement', data: periods.map(p => purchaseMap[p] || 0), borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,.12)', fill: true, tension: .3},
            ]
        },
        options: {plugins: {legend: {position: 'bottom'}}, scales: {y: {ticks: {callback: money}}}}
    });

    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: categorySales.map(c => c.category),
            datasets: [{data: categorySales.map(c => c.total), backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#20c997', '#fd7e14', '#6610f2']}]
        },
        options: {plugins: {legend: {position: 'bottom'}}}
    });
</script>
@endpush
