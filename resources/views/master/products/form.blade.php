@extends('layouts.app')
@section('title', $product->exists ? 'Edit Product' : 'Add Product')

@section('content')
<div class="row justify-content-center"><div class="col-lg-10">
    <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}">
        @csrf @if ($product->exists) @method('PUT') @endif
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-shoe-prints me-2 text-primary"></i>{{ $product->exists ? 'Edit' : 'New' }} Product</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Product name *</label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control" required>
                        <x-field-error field="name" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SKU</label>
                        <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="form-control" placeholder="Auto-generated if blank">
                        <x-field-error field="sku" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Barcode</label>
                        <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}" class="form-control">
                        <x-field-error field="barcode" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <x-field-error field="category_id" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Age group (Kids/Babies)</label>
                        <select name="age_group_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach ($ageGroups as $group)
                                <option value="{{ $group->id }}" @selected(old('age_group_id', $product->age_group_id) == $group->id)>{{ $group->name }} ({{ $group->min_age_months }}–{{ $group->max_age_months ?? '∞' }}m)</option>
                            @endforeach
                        </select>
                        <x-field-error field="age_group_id" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Brand</label>
                        <select name="brand_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) == $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        <x-field-error field="brand_id" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Style</label>
                        <select name="style_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach ($styles as $style)
                                <option value="{{ $style->id }}" @selected(old('style_id', $product->style_id) == $style->id)>{{ $style->name }}</option>
                            @endforeach
                        </select>
                        <x-field-error field="style_id" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Gender *</label>
                        <select name="gender" class="form-select" required>
                            @foreach (['men', 'women', 'kids', 'babies', 'unisex'] as $g)
                                <option value="{{ $g }}" @selected(old('gender', $product->gender) === $g)>{{ ucfirst($g) }}</option>
                            @endforeach
                        </select>
                        <x-field-error field="gender" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Cost price</label>
                        <input type="number" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" step="0.01" min="0" class="form-control">
                        <x-field-error field="cost_price" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Retail price</label>
                        <input type="number" name="retail_price" value="{{ old('retail_price', $product->retail_price) }}" step="0.01" min="0" class="form-control">
                        <x-field-error field="retail_price" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select">
                            <option value="active" @selected(old('status', $product->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $product->status) === 'inactive')>Inactive</option>
                        </select>
                        <x-field-error field="status" />
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $product->description) }}</textarea>
                        <x-field-error field="description" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ul me-2 text-info"></i>Variants (Size / Color / Pricing)</span>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addVariant"><i class="bi bi-plus-lg"></i> Add Row</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle" id="variantTable">
                        <thead><tr><th style="width:22%">Size</th><th style="width:22%">Color</th><th style="width:22%">Cost price</th><th style="width:22%">Retail price</th><th></th></tr></thead>
                        <tbody id="variantBody"></tbody>
                    </table>
                </div>
                <p class="text-muted small mb-0">Existing variants matching a size + color are updated; new combinations are added.</p>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Product</button>
            <a href="{{ route('products.index') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div></div>
@endsection

@php
$variantData = $product->exists
    ? $product->variants->map(fn ($v) => ['size' => $v->size, 'color' => $v->color, 'cost' => $v->cost_price, 'price' => $v->price])->values()->all()
    : [];
@endphp

@push('scripts')
<script>
    const body = document.getElementById('variantBody');
    const existing = @json($variantData);

    function addRow(size = '', color = '', cost = '', price = '') {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" name="variant_size[]" value="${size}" class="form-control form-control-sm" placeholder="e.g. 8"></td>
            <td><input type="text" name="variant_color[]" value="${color}" class="form-control form-control-sm" placeholder="e.g. Black"></td>
            <td><input type="number" step="0.01" name="variant_cost[]" value="${cost}" class="form-control form-control-sm"></td>
            <td><input type="number" step="0.01" name="variant_price[]" value="${price}" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-sm btn-light text-danger remove-row"><i class="bi bi-x-lg"></i></button></td>`;
        body.appendChild(row);
        row.querySelector('.remove-row').addEventListener('click', () => row.remove());
    }

    document.getElementById('addVariant').addEventListener('click', () => addRow());
    if (existing.length) { existing.forEach(v => addRow(v.size, v.color, v.cost, v.price)); }
    else { addRow(); }
</script>
@endpush
