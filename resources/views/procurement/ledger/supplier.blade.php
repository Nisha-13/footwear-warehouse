@extends('layouts.app')
@section('title', $supplier->company_name . ' — Ledger')
@section('subtitle', 'Account statement and payment history')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-lg-4"><x-stat label="Outstanding" :value="money($outstanding)" icon="bi-cash-stack" tone="warning" :hint="$outstanding > 0 ? 'Balance payable' : 'Fully settled'" /></div>
    <div class="col-lg-4"><x-stat label="Ledger Entries" :value="$entries->total()" icon="bi-journal-text" tone="primary" /></div>
    <div class="col-lg-4"><x-stat label="Status" :value="ucfirst($supplier->status)" icon="bi-check-circle" tone="info" /></div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-journal-text me-2 text-primary"></i>{{ $supplier->company_name }} Statement</span>
        <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i> Supplier Profile</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Entry</th><th>Date</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th><th>Status</th><th>By</th></tr></thead>
            <tbody>
            @forelse ($entries as $entry)
                <tr>
                    <td class="small fw-semibold">{{ $entry->entry_no }}</td>
                    <td class="small text-muted">{{ $entry->entry_date?->format('d M Y') }}</td>
                    <td class="small">{{ $entry->description }}</td>
                    <td class="text-end text-danger">{{ $entry->debit > 0 ? money($entry->debit) : '—' }}</td>
                    <td class="text-end text-success">{{ $entry->credit > 0 ? money($entry->credit) : '—' }}</td>
                    <td class="text-end fw-semibold">{{ money($entry->balance) }}</td>
                    <td>{!! status_badge($entry->payment_status) !!}</td>
                    <td class="small text-muted">{{ $entry->user?->name ?? 'System' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No ledger entries for this supplier.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($entries->hasPages())<div class="card-footer">{{ $entries->links() }}</div>@endif
</div>
@endsection
