@extends('layouts.app')
@section('title', $supplier->company_name)
@section('subtitle', 'Supplier profile and purchase history')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-lg-3"><x-stat label="Total Purchases" :value="$supplier->purchases->count()" icon="bi-cart-check" tone="primary" /></div>
    <div class="col-lg-3"><x-stat label="Outstanding" :value="money($outstanding)" icon="bi-journal-text" tone="warning" :hint="$outstanding > 0 ? 'Payable pending' : 'Fully settled'" /></div>
    <div class="col-lg-3"><x-stat label="Purchase Value" :value="money($supplier->purchases->sum('total'))" icon="bi-cash-stack" tone="success" /></div>
    <div class="col-lg-3"><x-stat label="Status" :value="ucfirst($supplier->status)" icon="bi-check-circle" tone="info" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-info-circle me-2 text-primary"></i>Contact Details</div>
            <div class="card-body">
                <div class="mb-2"><span class="text-muted small">Contact</span><div class="fw-semibold">{{ $supplier->contact_person ?? '—' }}</div></div>
                <div class="mb-2"><span class="text-muted small">Phone</span><div class="fw-semibold">{{ $supplier->phone ?? '—' }}</div></div>
                <div class="mb-2"><span class="text-muted small">Email</span><div class="fw-semibold">{{ $supplier->email ?? '—' }}</div></div>
                <div class="mb-2"><span class="text-muted small">Location</span><div class="fw-semibold">{{ $supplier->city }}, {{ $supplier->country }}</div></div>
                <div class="mb-2"><span class="text-muted small">Tax / NTN</span><div class="fw-semibold">{{ $supplier->tax_number ?? '—' }}</div></div>
                <a href="{{ route('ledger.supplier', $supplier) }}" class="btn btn-sm btn-outline-primary mt-2"><i class="bi bi-journal-text"></i> Full Ledger</a>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-receipt me-2 text-primary"></i>Recent Purchases</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Invoice</th><th>Date</th><th class="text-center">Items</th><th class="text-end">Total</th><th>Payment</th></tr></thead>
                    <tbody>
                    @forelse ($supplier->purchases as $purchase)
                        <tr>
                            <td><a href="{{ route('purchases.show', $purchase) }}" class="text-decoration-none fw-semibold">{{ $purchase->invoice_no }}</a></td>
                            <td class="small text-muted">{{ $purchase->purchase_date->format('d M Y') }}</td>
                            <td class="text-center">{{ $purchase->item_count }}</td>
                            <td class="text-end fw-semibold">{{ money($purchase->total) }}</td>
                            <td>{!! status_badge($purchase->payment_status) !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No purchases yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
