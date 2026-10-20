@extends('layouts.app')
@section('title', 'New Transfer')
@section('subtitle', 'Move stock between warehouses or to a retail shop')

@section('content')
<form method="POST" action="{{ route('transfers.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-signpost-split me-2 text-primary"></i>Route</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Direction *</label>
                            <select name="direction" id="direction" class="form-select" required>
                                <option value="warehouse_to_warehouse" @selected(old('direction') === 'warehouse_to_warehouse')>Warehouse → Warehouse</option>
                                <option value="warehouse_to_shop" @selected(old('direction', 'warehouse_to_shop') === 'warehouse_to_shop')>Warehouse → Shop</option>
                            </select>
                            <x-field-error field="direction" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">From warehouse *</label>
                            <select name="from_warehouse_id" class="form-select" required>
                                <option value="">— Select —</option>
                                @foreach ($sourceWarehouses as $w)
                                    <option value="{{ $w->id }}" @selected(old('from_warehouse_id') == $w->id)>{{ $w->name }}</option>
                                @endforeach
                            </select>
                            <x-field-error field="from_warehouse_id" />
                        </div>
                        <div class="col-md-4 dest-warehouse">
                            <label class="form-label">To warehouse *</label>
                            <select name="to_warehouse_id" class="form-select">
                                <option value="">— Select —</option>
                                @foreach ($regionalWarehouses as $w)
                                    <option value="{{ $w->id }}" @selected(old('to_warehouse_id') == $w->id)>{{ $w->name }}</option>
                                @endforeach
                            </select>
                            <x-field-error field="to_warehouse_id" />
                        </div>
                        <div class="col-md-4 dest-shop d-none">
                            <label class="form-label">To shop *</label>
                            <select name="to_shop_id" id="toShop" class="form-select">
                                <option value="">— Select —</option>
                                @foreach ($shops as $shop)
                                    <option value="{{ $shop->id }}" data-warehouse="{{ $shop->warehouse_id }}" @selected(old('to_shop_id') == $shop->id)>{{ $shop->display_name }}</option>
                                @endforeach
                            </select>
                            <x-field-error field="to_shop_id" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Assign rider</label>
                            <select name="rider_id" id="riderSelect" class="form-select">
                                <option value="">— Optional —</option>
                                @foreach ($riders as $rider)
                                    <option value="{{ $rider->id }}" data-warehouse="{{ $rider->warehouse_id }}" @selected(old('rider_id') == $rider->id)>{{ $rider->name }}</option>
                                @endforeach
                            </select>
                            <x-field-error field="rider_id" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payment mode *</label>
                            <select name="payment_mode" class="form-select" required>
                                <option value="credit" @selected(old('payment_mode') === 'credit')>Credit</option>
                                <option value="full_cash" @selected(old('payment_mode') === 'full_cash')>Full cash</option>
                                <option value="logistics_line" @selected(old('payment_mode') === 'logistics_line')>Logistics line</option>
                            </select>
                            <x-field-error field="payment_mode" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expected date</label>
                            <input type="date" name="expected_date" value="{{ old('expected_date') }}" class="form-control">
                            <x-field-error field="expected_date" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Shipping cost</label>
                            <input type="number" name="shipping_cost" id="shipping" value="{{ old('shipping_cost', 0) }}" step="0.01" min="0" class="form-control calc">
                            <x-field-error field="shipping_cost" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box-seam me-2 text-info"></i>Items</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addItem"><i class="bi bi-plus-lg"></i> Add Item</button>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th style="width:60%">Product variant</th><th>Qty</th><th class="text-end">Total</th><th></th></tr></thead>
                        <tbody id="itemBody"></tbody>
                        <tfoot><tr><td colspan="2" class="text-end fw-semibold">Items total</td><td class="text-end fw-bold" id="itemsTotal">0.00</td><td></td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-calculator me-2 text-success"></i>Summary</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between"><span>Items total</span><span id="sumItems">0.00</span></div>
                    <div class="d-flex justify-content-between"><span>Shipping</span><span id="sumShip">0.00</span></div>
                    <hr>
                    <div class="d-flex justify-content-between fs-5 fw-bold"><span>Transfer value</span><span id="sumTotal">0.00</span></div>
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
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Create Transfer</button>
                <a href="{{ route('transfers.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </div>
    </div>
</form>

<template id="variantOptions">
    <option value="">— Select product variant —</option>
    @foreach ($destinations as $variant)
        <option value="{{ $variant->id }}" data-price="{{ $variant->price }}">{{ $variant->product?->name }} — {{ $variant->size }}/{{ $variant->color }} ({{ $variant->sku }})</option>
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
        });
    }

    function bindRow(row) {
        row.querySelector('.remove-row').addEventListener('click', () => { row.remove(); reindex(); recalc(); });
        row.querySelector('.variant-select').addEventListener('change', recalc);
        row.querySelector('.qty').addEventListener('input', recalc);
    }

    function recalc() {
        let itemsTotal = 0;
        body.querySelectorAll('tr').forEach(row => {
            const opt = row.querySelector('.variant-select').selectedOptions[0];
            const price = opt && opt.dataset.price ? +opt.dataset.price : 0;
            const line = price * (+row.querySelector('.qty').value || 0);
            row.querySelector('.line-total').textContent = fmt(line);
            itemsTotal += line;
        });
        const ship = +document.getElementById('shipping').value || 0;
        document.getElementById('itemsTotal').textContent = fmt(itemsTotal);
        document.getElementById('sumItems').textContent = fmt(itemsTotal);
        document.getElementById('sumShip').textContent = fmt(ship);
        document.getElementById('sumTotal').textContent = fmt(itemsTotal + ship);
    }

    const direction = document.getElementById('direction');
    function toggleDest() {
        const toShop = direction.value === 'warehouse_to_shop';
        document.querySelector('.dest-shop').classList.toggle('d-none', !toShop);
        document.querySelector('.dest-warehouse').classList.toggle('d-none', toShop);
    }
    direction.addEventListener('change', toggleDest);
    toggleDest();

    // Only shops and riders belonging to the selected source warehouse are valid.
    const fromWarehouse = document.querySelector('[name=from_warehouse_id]');
    const toShopSelect = document.getElementById('toShop');
    const riderSelect = document.getElementById('riderSelect');
    function filterOptionsByWarehouse(select) {
        const wh = fromWarehouse.value;
        let selectionVisible = false;
        select.querySelectorAll('option[data-warehouse]').forEach(opt => {
            const visible = !wh || opt.dataset.warehouse === wh;
            opt.hidden = !visible;
            if (visible && opt.selected) selectionVisible = true;
        });
        if (!selectionVisible) select.value = '';
    }
    function filterDependentsByWarehouse() {
        filterOptionsByWarehouse(toShopSelect);
        filterOptionsByWarehouse(riderSelect);
    }
    fromWarehouse.addEventListener('change', filterDependentsByWarehouse);
    filterDependentsByWarehouse();

    document.getElementById('addItem').addEventListener('click', addRow);
    document.getElementById('shipping').addEventListener('input', recalc);
    addRow();
</script>
@endpush
