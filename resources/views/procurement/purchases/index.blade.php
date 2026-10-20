@extends('layouts.app')
@section('title', 'Purchases')
@section('subtitle', 'B2B procurement from footwear manufacturers')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex flex-wrap gap-2" method="GET">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Invoice no...">
            <select name="supplier_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All suppliers</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->company_name }}</option>
                @endforeach
            </select>
            <select name="payment_status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Any payment</option>
                @foreach (['paid', 'partial', 'unpaid'] as $p)
                    <option value="{{ $p }}" @selected(request('payment_status') === $p)>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
        <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New Purchase</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Invoice</th><th>Supplier</th><th>Warehouse</th><th>Date</th><th class="text-center">Items</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Payment</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($purchases as $purchase)
                <tr>
                    <td><a href="{{ route('purchases.show', $purchase) }}" class="fw-semibold text-decoration-none">{{ $purchase->invoice_no }}</a></td>
                    <td class="small">{{ $purchase->supplier?->company_name }}</td>
                    <td class="small">{{ $purchase->warehouse?->name }}</td>
                    <td class="small text-muted">{{ $purchase->purchase_date->format('d M Y') }}</td>
                    <td class="text-center">{{ $purchase->item_count }}</td>
                    <td class="text-end fw-semibold">{{ money($purchase->total) }}</td>
                    <td class="text-end text-success">{{ money($purchase->paid_amount) }}</td>
                    <td class="text-end text-danger">{{ money($purchase->balance) }}</td>
                    <td>{!! status_badge($purchase->payment_status) !!}</td>
                    <td class="text-end"><a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted py-4">No purchases found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($purchases->hasPages())<div class="card-footer">{{ $purchases->links() }}</div>@endif
</div>
@endsection
