@extends('layouts.app')
@section('title', 'Sale ' . $sale->invoice_no)
@section('subtitle', $sale->shop?->display_name . ' · ' . $sale->sale_date?->format('d M Y'))

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt me-2 text-primary"></i>Invoice {{ $sale->invoice_no }}</span>
                <span>{!! status_badge($sale->payment_status) !!}</span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Product</th><th>Variant</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @foreach ($sale->items as $item)
                        <tr>
                            <td class="fw-semibold">{{ $item->productVariant?->product?->name }}</td>
                            <td class="small">{{ $item->productVariant?->size }}/{{ $item->productVariant?->color }}</td>
                            <td class="text-end">{{ $item->quantity }}</td>
                            <td class="text-end">{{ money($item->unit_price) }}</td>
                            <td class="text-end fw-semibold">{{ money($item->total) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td colspan="4" class="text-end text-muted">Subtotal</td><td class="text-end">{{ money($sale->subtotal) }}</td></tr>
                        <tr><td colspan="4" class="text-end text-muted">Discount</td><td class="text-end">-{{ money($sale->discount) }}</td></tr>
                        <tr><td colspan="4" class="text-end text-muted">Tax</td><td class="text-end">{{ money($sale->tax) }}</td></tr>
                        <tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td class="text-end">{{ money($sale->total) }}</td></tr>
                        <tr><td colspan="4" class="text-end text-success">Paid</td><td class="text-end text-success">{{ money($sale->paid_amount) }}</td></tr>
                        <tr><td colspan="4" class="text-end text-danger">Balance</td><td class="text-end text-danger">{{ money($sale->balance) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2 text-primary"></i>Details</div>
            <div class="card-body small">
                <div class="mb-2"><span class="text-muted">Shop</span><div class="fw-semibold">{{ $sale->shop?->display_name }}</div></div>
                <div class="mb-2"><span class="text-muted">Cashier</span><div class="fw-semibold">{{ $sale->user?->name ?? '—' }}</div></div>
                <div class="mb-2"><span class="text-muted">Customer</span><div class="fw-semibold">{{ $sale->customer_name ?? 'Walk-in' }}</div></div>
                <div class="mb-2"><span class="text-muted">Phone</span><div class="fw-semibold">{{ $sale->customer_phone ?? '—' }}</div></div>
                <div class="mb-2"><span class="text-muted">Payment mode</span><div class="fw-semibold">{{ ucwords(str_replace('_', ' ', $sale->payment_mode)) }}</div></div>
                <a href="{{ route('shop.sales.index') }}" class="btn btn-sm btn-light mt-2"><i class="bi bi-arrow-left"></i> Back to Sales</a>
            </div>
        </div>
    </div>
</div>
@endsection
