@extends('layouts.app')
@section('title', 'Sales')
@section('subtitle', $shop?->display_name . ' · receipts and revenue')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Sales History</span>
        <a href="{{ route('shop.sales.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-cart-plus"></i> New Sale</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Invoice</th><th>Date</th><th>Customer</th><th class="text-center">Items</th><th class="text-end">Total</th><th>Payment</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($sales as $sale)
                <tr>
                    <td><a href="{{ route('shop.sales.show', $sale) }}" class="fw-semibold text-decoration-none">{{ $sale->invoice_no }}</a></td>
                    <td class="small text-muted">{{ $sale->sale_date?->format('d M Y') }}</td>
                    <td class="small">{{ $sale->customer_name ?? 'Walk-in' }}</td>
                    <td class="text-center">{{ $sale->item_count }}</td>
                    <td class="text-end fw-semibold">{{ money($sale->total) }}</td>
                    <td>{!! status_badge($sale->payment_status) !!}</td>
                    <td class="text-end"><a href="{{ route('shop.sales.show', $sale) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No sales yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($sales->hasPages())<div class="card-footer">{{ $sales->links() }}</div>@endif
</div>
@endsection
