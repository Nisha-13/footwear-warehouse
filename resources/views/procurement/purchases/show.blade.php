@extends('layouts.app')
@section('title', 'Purchase ' . $purchase->invoice_no)
@section('subtitle', $purchase->supplier?->company_name . ' · ' . $purchase->purchase_date->format('d M Y'))

@section('content')
<div class="row g-3 mb-3">
    <div class="col-lg-3"><x-stat label="Grand Total" :value="money($purchase->total)" icon="bi-cash-stack" tone="primary" /></div>
    <div class="col-lg-3"><x-stat label="Paid" :value="money($purchase->paid_amount)" icon="bi-check-circle" tone="success" /></div>
    <div class="col-lg-3"><x-stat label="Balance" :value="money($purchase->balance)" icon="bi-exclamation-circle" tone="danger" /></div>
    <div class="col-lg-3"><x-stat label="Payment Status" :value="ucfirst($purchase->payment_status)" icon="bi-credit-card" tone="warning" :hint="$purchase->due_date ? 'Due '.$purchase->due_date->format('d M Y') : null" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-box-seam me-2 text-primary"></i>Items ({{ $purchase->item_count }} units)</span>
                <span>{!! status_badge($purchase->status) !!}</span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Product</th><th>Variant</th><th class="text-end">Qty</th><th class="text-end">Unit cost</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @foreach ($purchase->items as $item)
                        <tr>
                            <td class="fw-semibold">{{ $item->productVariant?->product?->name ?? '—' }}</td>
                            <td class="small">{{ $item->productVariant?->size }}/{{ $item->productVariant?->color }}<div class="text-muted">{{ $item->productVariant?->sku }}</div></td>
                            <td class="text-end">{{ $item->quantity }}</td>
                            <td class="text-end">{{ money($item->unit_cost) }}</td>
                            <td class="text-end fw-semibold">{{ money($item->total) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td colspan="4" class="text-end text-muted">Subtotal</td><td class="text-end">{{ money($purchase->subtotal) }}</td></tr>
                        <tr><td colspan="4" class="text-end text-muted">Tax</td><td class="text-end">{{ money($purchase->tax) }}</td></tr>
                        <tr><td colspan="4" class="text-end text-muted">Shipping</td><td class="text-end">{{ money($purchase->shipping) }}</td></tr>
                        <tr><td colspan="4" class="text-end text-muted">Discount</td><td class="text-end">-{{ money($purchase->discount) }}</td></tr>
                        <tr class="fw-bold"><td colspan="4" class="text-end">Grand Total</td><td class="text-end">{{ money($purchase->total) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-truck me-2 text-primary"></i>Supplier & Receipt</div>
            <div class="card-body small">
                <div class="mb-2"><span class="text-muted">Supplier</span><div class="fw-semibold">{{ $purchase->supplier?->company_name }}</div></div>
                <div class="mb-2"><span class="text-muted">Warehouse</span><div class="fw-semibold">{{ $purchase->warehouse?->name }}</div></div>
                <div class="mb-2"><span class="text-muted">Recorded by</span><div class="fw-semibold">{{ $purchase->user?->name ?? 'System' }}</div></div>
                <div class="mb-2"><span class="text-muted">Payment method</span><div class="fw-semibold">{{ ucfirst($purchase->payment_method ?? '—') }}</div></div>
                @if ($purchase->notes)<div class="mb-2"><span class="text-muted">Notes</span><div>{{ $purchase->notes }}</div></div>@endif
                <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" class="mt-2" onsubmit="return confirm('Reverse this purchase and restore stock?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-arrow-counterclockwise"></i> Reverse Purchase</button>
                </form>
            </div>
        </div>

        @if ($purchase->balance > 0)
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-cash-coin me-2 text-success"></i>Record Payment</div>
            <div class="card-body">
                <form method="POST" action="{{ route('purchases.payment', $purchase) }}">
                    @csrf
                    <label class="form-label small">Amount (max {{ money($purchase->balance) }})</label>
                    <input type="number" name="amount" step="0.01" min="0.01" max="{{ $purchase->balance }}" value="{{ $purchase->balance }}" class="form-control form-control-sm mb-2" required>
                    <x-field-error field="amount" />
                    <label class="form-label small">Method</label>
                    <select name="payment_method" class="form-select form-select-sm mb-2">
                        @foreach (['cash', 'bank', 'cheque', 'credit'] as $m)
                            <option value="{{ $m }}" @selected($purchase->payment_method === $m)>{{ ucfirst($m) }}</option>
                        @endforeach
                    </select>
                    <x-field-error field="payment_method" />
                    <label class="form-label small">Date</label>
                    <input type="date" name="entry_date" value="{{ now()->toDateString() }}" class="form-control form-control-sm mb-3" required>
                    <x-field-error field="entry_date" />
                    <button class="btn btn-sm btn-success w-100"><i class="bi bi-save me-1"></i> Add Payment</button>
                </form>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header"><i class="bi bi-journal-text me-2 text-primary"></i>Ledger Entries</div>
            <div class="list-group list-group-flush small">
                @forelse ($purchase->ledgerEntries as $entry)
                    <div class="list-group-item d-flex justify-content-between">
                        <div>
                            <div class="fw-semibold">{{ $entry->entry_no }}</div>
                            <div class="text-muted">{{ $entry->entry_date?->format('d M Y') }} · {{ $entry->description }}</div>
                        </div>
                        <div class="text-end">
                            <div class="text-danger">Dr {{ money($entry->debit) }}</div>
                            <div class="text-success">Cr {{ money($entry->credit) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-muted">No ledger entries.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
