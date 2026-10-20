@extends('layouts.app')
@section('title', 'New Purchase')
@section('subtitle', 'Record a B2B procurement and receive stock')

@section('content')
<form method="POST" action="{{ route('purchases.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-receipt me-2 text-primary"></i>Purchase Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Supplier *</label>
                            <select name="supplier_id" class="form-select" required>
                                <option value="">— Select supplier —</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->company_name }}</option>
                                @endforeach
                            </select>
                            <x-field-error field="supplier_id" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Receiving warehouse *</label>
                            <select name="warehouse_id" class="form-select" required>
                                <option value="">— Select warehouse —</option>
                                @foreach ($warehouses as $w)
                                    <option value="{{ $w->id }}" @selected(old('warehouse_id') == $w->id)>{{ $w->name }}</option>
                                @endforeach
                            </select>
                            <x-field-error field="warehouse_id" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Purchase date *</label>
                            <input type="date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" class="form-control" required>
                            <x-field-error field="purchase_date" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Due date</label>
                            <input type="date" name="due_date" value="{{ old('due_date') }}" class="form-control">
                            <x-field-error field="due_date" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payment method</label>
                            <select name="payment_method" class="form-select">
                                <option value="">— Select —</option>
                                @foreach (['cash', 'bank', 'cheque', 'credit'] as $m)
                                    <option value="{{ $m }}" @selected(old('payment_method') === $m)>{{ ucfirst($m) }}</option>
                                @endforeach
                            </select>
                            <x-field-error field="payment_method" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box-seam me-2 text-info"></i>Order Items</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addItem"><i class="bi bi-plus-lg"></i> Add Item</button>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th style="width:50%">Product variant</th><th>Qty</th><th>Unit cost</th><th class="text-end">Line total</th><th></th></tr></thead>
                        <tbody id="itemBody"></tbody>
                        <tfoot>
                            <tr><td colspan="3" class="text-end fw-semibold">Subtotal</td><td class="text-end fw-bold" id="subtotalCell">0.00</td><td></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-calculator me-2 text-success"></i>Totals & Payment</div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label small">Tax</label>
                        <input type="number" name="tax" id="tax" value="{{ old('tax', 0) }}" step="0.01" min="0" class="form-control form-control-sm calc">
                        <x-field-error field="tax" />
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Shipping</label>
                        <input type="number" name="shipping" id="shipping" value="{{ old('shipping', 0) }}" step="0.01" min="0" class="form-control form-control-sm calc">
                        <x-field-error field="shipping" />
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Discount</label>
                        <input type="number" name="discount" id="discount" value="{{ old('discount', 0) }}" step="0.01" min="0" class="form-control form-control-sm calc">
                        <x-field-error field="discount" />
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between"><span>Subtotal</span><span id="subTotal">0.00</span></div>
                    <div class="d-flex justify-content-between fs-5 fw-bold mt-2"><span>Grand total</span><span id="grandTotal">0.00</span></div>
                    <hr>
                    <div class="mb-2">
                        <label class="form-label small">Amount paid now</label>
                        <input type="number" name="paid_amount" id="paid" value="{{ old('paid_amount', 0) }}" step="0.01" min="0" class="form-control form-control-sm calc">
                        <x-field-error field="paid_amount" />
                    </div>
                    <div class="d-flex justify-content-between text-danger"><span>Balance</span><span id="balance">0.00</span></div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <label class="form-label small">Notes</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="3">{{ old('notes') }}</textarea>
                    <x-field-error field="notes" />
                </div>
            </div>
            <div class="d-grid gap-2">
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Record Purchase</button>
                <a href="{{ route('purchases.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </div>
    </div>
</form>

<template id="variantOptions">
    <option value="">— Select product variant —</option>
    @foreach ($variants as $variant)
        <option value="{{ $variant->id }}" data-cost="{{ $variant->cost_price }}">{{ $variant->product?->name }} — {{ $variant->size }}/{{ $variant->color }} ({{ $variant->sku }})</option>
    @endforeach
</template>
@endsection

@push('scripts')
<script>
    const body = document.getElementById('itemBody');
    const optionsHtml = document.getElementById('variantOptions').innerHTML;
    const fmt = n => Number(n || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

    function addRow() {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><select name="items[__i__][product_variant_id]" class="form-select form-select-sm variant-select" required></select></td>
            <td><input type="number" name="items[__i__][quantity]" value="1" min="1" class="form-control form-control-sm qty" style="width:80px" required></td>
            <td><input type="number" name="items[__i__][unit_cost]" value="0" step="0.01" min="0" class="form-control form-control-sm cost" style="width:100px" required></td>
            <td class="text-end line-total">0.00</td>
            <td><button type="button" class="btn btn-sm btn-light text-danger remove-row"><i class="bi bi-x-lg"></i></button></td>`;
        const sel = row.querySelector('.variant-select');
        sel.innerHTML = optionsHtml;
        body.appendChild(row);
        reindex();
        bindRow(row);
        recalc();
    }

    function reindex() {
        body.querySelectorAll('tr').forEach((row, i) => {
            row.querySelector('.variant-select').name = `items[${i}][product_variant_id]`;
            row.querySelector('.qty').name = `items[${i}][quantity]`;
            row.querySelector('.cost').name = `items[${i}][unit_cost]`;
        });
    }

    function bindRow(row) {
        row.querySelector('.remove-row').addEventListener('click', () => { row.remove(); reindex(); recalc(); });
        row.querySelector('.variant-select').addEventListener('change', e => {
            const opt = e.target.selectedOptions[0];
            if (opt && opt.dataset.cost) row.querySelector('.cost').value = opt.dataset.cost;
            recalc();
        });
        row.querySelectorAll('.qty, .cost').forEach(el => el.addEventListener('input', recalc));
    }

    function recalc() {
        let subtotal = 0;
        body.querySelectorAll('tr').forEach(row => {
            const line = (row.querySelector('.qty')?.value || 0) * (row.querySelector('.cost')?.value || 0);
            row.querySelector('.line-total').textContent = fmt(line);
            subtotal += line;
        });
        const tax = +document.getElementById('tax').value || 0;
        const ship = +document.getElementById('shipping').value || 0;
        const disc = +document.getElementById('discount').value || 0;
        const paid = +document.getElementById('paid').value || 0;
        const total = subtotal + tax + ship - disc;
        document.getElementById('subtotalCell').textContent = fmt(subtotal);
        document.getElementById('subTotal').textContent = fmt(subtotal);
        document.getElementById('grandTotal').textContent = fmt(total);
        document.getElementById('balance').textContent = fmt(Math.max(0, total - paid));
    }

    document.getElementById('addItem').addEventListener('click', addRow);
    document.querySelectorAll('.calc').forEach(function(el) { el.addEventListener('input', recalc); });
    addRow();
</script>
@endpush
