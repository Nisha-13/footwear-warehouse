@extends('layouts.app')
@section('title', 'Supplier Ledger')
@section('subtitle', 'Accounts payable across all suppliers')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-lg-4"><x-stat label="Total Debit (Payable)" :value="money($summary['total_debit'])" icon="bi-arrow-down-circle" tone="danger" /></div>
    <div class="col-lg-4"><x-stat label="Total Credit (Paid)" :value="money($summary['total_credit'])" icon="bi-arrow-up-circle" tone="success" /></div>
    <div class="col-lg-4"><x-stat label="Outstanding Balance" :value="money($summary['outstanding'])" icon="bi-cash-stack" tone="warning" /></div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex flex-wrap gap-2" method="GET">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Entry no / description...">
            <select name="supplier_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All suppliers</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->company_name }}</option>
                @endforeach
            </select>
            <select name="payment_status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Any status</option>
                @foreach (['paid', 'partial', 'unpaid'] as $p)
                    <option value="{{ $p }}" @selected(request('payment_status') === $p)>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
            <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Entry</th><th>Date</th><th>Supplier</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($entries as $entry)
                <tr>
                    <td class="small fw-semibold">{{ $entry->entry_no }}</td>
                    <td class="small text-muted">{{ $entry->entry_date?->format('d M Y') }}</td>
                    <td class="small">
                        @if ($entry->party instanceof \App\Models\Supplier)
                            <a href="{{ route('ledger.supplier', $entry->party) }}" class="text-decoration-none">{{ $entry->party->company_name }}</a>
                        @else
                            {{ $entry->party?->name ?? '—' }}
                        @endif
                    </td>
                    <td class="small text-muted">{{ $entry->description }}</td>
                    <td class="text-end text-danger">{{ $entry->debit > 0 ? money($entry->debit) : '—' }}</td>
                    <td class="text-end text-success">{{ $entry->credit > 0 ? money($entry->credit) : '—' }}</td>
                    <td class="text-end fw-semibold">{{ money($entry->balance) }}</td>
                    <td>{!! status_badge($entry->payment_status) !!}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No ledger entries.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($entries->hasPages())<div class="card-footer">{{ $entries->links() }}</div>@endif
</div>
@endsection
