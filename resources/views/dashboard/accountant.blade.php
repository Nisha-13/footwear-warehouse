@extends('layouts.app')

@section('title', 'Accountant Dashboard')
@section('subtitle', 'Finance overview and supplier ledger')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="wms-avatar-lg bg-primary-subtle text-primary"><i class="bi bi-cart-check"></i></div>
                <div>
                    <div class="text-muted small">Purchases (This Month)</div>
                    <div class="fs-4 fw-semibold">Rs {{ number_format($stats['purchases_month'], 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="wms-avatar-lg bg-success-subtle text-success"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="text-muted small">Sales (This Month)</div>
                    <div class="fs-4 fw-semibold">Rs {{ number_format($stats['sales_month'], 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="wms-avatar-lg bg-warning-subtle text-warning"><i class="bi bi-journal-arrow-up"></i></div>
                <div>
                    <div class="text-muted small">Total Debits (Payables)</div>
                    <div class="fs-4 fw-semibold">Rs {{ number_format($stats['payables_total'], 0) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="wms-avatar-lg bg-info-subtle text-info"><i class="bi bi-journal-arrow-down"></i></div>
                <div>
                    <div class="text-muted small">Outstanding (Net)</div>
                    <div class="fs-4 fw-semibold">Rs {{ number_format($stats['receivable_outstanding'], 0) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up me-2 text-primary"></i>Sales Trend (Last 14 Days)</span>
                <a href="{{ route('reports.index') }}" class="btn btn-sm btn-light">View Reports</a>
            </div>
            <div class="card-body">
                <canvas id="accountantTrend" height="110"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-info-circle me-2 text-muted"></i>Quick Finance</div>
            <div class="list-group list-group-flush">
                <a href="{{ route('purchases.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-cart-check me-2"></i>All Purchases</span>
                    <span class="badge bg-primary-subtle text-primary">{{ \App\Models\Purchase::count() }}</span>
                </a>
                <a href="{{ route('ledger.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-journal-text me-2"></i>Supplier Ledger</span>
                    <span class="badge bg-warning-subtle text-warning">{{ $stats['suppliers'] }} suppliers</span>
                </a>
                <a href="{{ route('inventory.movements') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-arrow-left-right me-2"></i>Inventory Movements
                </a>
                <a href="{{ route('reports.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i>Financial Reports
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cart-check me-2 text-primary"></i>Recent Purchases</span>
                <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-light">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Purchase No</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPurchases as $purchase)
                            <tr>
                                <td><a href="{{ route('purchases.show', $purchase) }}" class="fw-semibold">{{ $purchase->purchase_no }}</a></td>
                                <td>{{ $purchase->supplier->company_name ?? '—' }}</td>
                                <td>{{ optional($purchase->purchase_date)->format('d M Y') }}</td>
                                <td class="text-end">Rs {{ number_format($purchase->total, 2) }}</td>
                                <td><span class="badge bg-light text-dark text-uppercase">{{ str_replace('_',' ', $purchase->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No purchases yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-journal-text me-2 text-warning"></i>Recent Ledger Entries</span>
                <a href="{{ route('ledger.index') }}" class="btn btn-sm btn-light">View Ledger</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Entry No</th>
                            <th>Party</th>
                            <th>Date</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLedger as $entry)
                            <tr>
                                <td class="fw-semibold">{{ $entry->entry_no }}</td>
                                <td>{{ class_basename($entry->party_type) }} #{{ $entry->party_id }}</td>
                                <td>{{ optional($entry->entry_date)->format('d M Y') }}</td>
                                <td class="text-end">Rs {{ number_format($entry->debit, 2) }}</td>
                                <td class="text-end">Rs {{ number_format($entry->credit, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No ledger entries yet</td></tr>
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
    const trendEl = document.getElementById('accountantTrend');
    if (trendEl) {
        const ctx = trendEl.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($salesTrend->pluck('day')->map(fn($d)=>\Illuminate\Support\Carbon::parse($d)->format('d M'))->values()) !!},
                datasets: [{
                    label: 'Sales (Rs)',
                    data: {!! json_encode($salesTrend->pluck('total')->map(fn($v)=>(float)$v)->values()) !!},
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79,70,229,.08)',
                    tension: .4,
                    fill: true,
                    pointRadius: 3
                }]
            },
            options: {
                scales: { y: { beginAtZero: true, ticks: { callback: v => 'Rs ' + Number(v).toLocaleString() } } },
                plugins: { legend: { display: false } }
            }
        });
    }
</script>
@endpush
