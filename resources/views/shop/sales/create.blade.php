@extends('layouts.app')
@section('title', 'New Sale')
@section('subtitle', $shop?->display_name . ' · point of sale')

@section('content')
<form method="POST" action="{{ route('shop.sales.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-cart me-2 text-primary"></i>Cart</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addItem"><i class="bi bi-plus-lg"></i> Add Item</button>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th style="width:50%">Product variant</th><th>Qty</th><th>Price</th><th class="text-end">Total</th><th></th></tr></thead>
                        <tbody id="itemBody"></tbody>
                        <tfoot><tr><td colspan="3" class="text-end fw-semibold">Subtotal</td><td class="text-end fw-bold" id="subtotal">0.00</td><td></td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-person me-2 text-primary"></i>Customer</div>
                <div class="card-body">
                    <input type="text" name="customer_name" value="{{ old('customer_name') }}" class="form-control form-control-sm mb-2" placeholder="Customer name (optional)">
                    <x-field-error field="customer_name" />
                    <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" class="form-control form-control-sm" placeholder="Phone (optional)">
                    <x-field-error field="customer_phone" />
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-credit-card me-2 text-success"></i>Payment</div>
                <div class="card-body">
                    <label class="form-label small">Payment mode *</label>
                    <select name="payment_mode" class="form-select form-select-sm" required>
                        <option value="full_cash" @selected(old('payment_mode') === 'full_cash')>Full cash</option>
                        <option value="card" @selected(old('payment_mode') === 'card')>Card</option>
                        <option value="online" @selected(old('payment_mode') === 'online')>Online</option>
                        <option value="credit" @selected(old('payment_mode') === 'credit')>Credit</option>
                    </select>
                    <x-field-error field="payment_mode" />
                    <div class="d-flex justify-content-between fs-4 fw-bold mt-3"><span>Total</span><span id="grandTotal">0.00</span></div>
                </div>
            </div>
            <div class="d-grid gap-2">
                <button class="btn btn-success btn-lg"><i class="bi bi-check-lg me-1"></i> Complete Sale</button>
                <a href="{{ route('shop.sales.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </div>
    </div>
</form>

<template id="stockOptions">
    <option value="">— Select product —</option>
    @foreach ($stocks as $stock)
        <option value="{{ $stock->productVariant->id }}" data-price="{{ $stock->productVariant->price }}" data-stock="{{ $stock->quantity }}">
            {{ $stock->productVariant->product->name }} — {{ $stock->productVariant->size }}/{{ $stock->productVariant->color }} ({{ $stock->quantity }} in stock)
        </option>
    @endforeach
</template>
@endsection

@push('scripts')
<script>
    const body = document.getElementById('itemBody');
    const optionsHtml = document.getElementById('stockOptions').innerHTML;
    const fmt = n => Number(n || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

    function addRow() {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><select class="form-select form-select-sm variant-select" required></select></td>
            <td><input type="number" value="1" min="1" class="form-control form-control-sm qty" style="width:80px" required></td>
            <td><input type="number" step="0.01" min="0" class="form-control form-control-sm price" style="width:100px" required></td>
            <td class="text-end line-total">0.00</td>
            <td><button type="button" class="btn btn-sm btn-light text-danger remove-row"><i class="bi bi-x-lg"></i></button></td>`;
        row.querySelector('.variant-select').innerHTML = optionsHtml;
        body.appendChild(row);
        reindex();
        bindRow(row);
        recalc();
    }

    function reindex() {
        body.querySelectorAll('tr').forEach((row, i) => {
            row.querySelector('.variant-select').name = `items[${i}][product_variant_id]`;
            row.querySelector('.qty').name = `items[${i}][quantity]`;
            row.querySelector('.price').name = `items[${i}][unit_price]`;
        });
    }

    function bindRow(row) {
        row.querySelector('.remove-row').addEventListener('click', () => { row.remove(); reindex(); recalc(); });
        row.querySelector('.variant-select').addEventListener('change', e => {
            const opt = e.target.selectedOptions[0];
            if (opt && opt.dataset.price) row.querySelector('.price').value = opt.dataset.price;
            recalc();
        });
        row.querySelectorAll('.qty, .price').forEach(el => el.addEventListener('input', recalc));
    }

    function recalc() {
        let subtotal = 0;
        body.querySelectorAll('tr').forEach(row => {
            const line = (+row.querySelector('.qty').value || 0) * (+row.querySelector('.price').value || 0);
            row.querySelector('.line-total').textContent = fmt(line);
            subtotal += line;
        });
        document.getElementById('subtotal').textContent = fmt(subtotal);
        document.getElementById('grandTotal').textContent = fmt(subtotal);
    }

    document.getElementById('addItem').addEventListener('click', addRow);
    addRow();
</script>
@endpush
