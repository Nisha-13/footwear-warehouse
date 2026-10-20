@extends('layouts.app')
@section('title', $product->name)
@section('subtitle', $product->sku . ' · ' . ucfirst($product->gender) . ' footwear')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-lg-3"><x-stat label="Variants" :value="$product->variants->count()" icon="bi-boxes" tone="primary" :hint="$product->variants->sum('total_stock').' units in stock'" /></div>
    <div class="col-lg-3"><x-stat label="Cost Price" :value="money($product->cost_price)" icon="bi-cash-stack" tone="warning" hint="Latest B2B cost" /></div>
    <div class="col-lg-3"><x-stat label="Retail Price" :value="money($product->retail_price)" icon="bi-tag" tone="success" hint="Recommended RRP" /></div>
    <div class="col-lg-3"><x-stat label="Status" :value="ucfirst($product->status)" icon="bi-check-circle" tone="info" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-info-circle me-2 text-primary"></i>Product Details</span>
                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            </div>
            <div class="card-body">
                <div class="mb-2"><span class="text-muted small">Brand</span><div class="fw-semibold">{{ $product->brand?->name ?? '—' }}</div></div>
                <div class="mb-2"><span class="text-muted small">Category</span><div class="fw-semibold">{{ $product->category?->name ?? '—' }}</div></div>
                <div class="mb-2"><span class="text-muted small">Age Group</span><div class="fw-semibold">{{ $product->ageGroup?->name ?? '—' }}</div></div>
                <div class="mb-2"><span class="text-muted small">Style</span><div class="fw-semibold">{{ $product->style?->name ?? '—' }}</div></div>
                <div class="mb-2"><span class="text-muted small">Barcode</span><div class="fw-semibold">{{ $product->barcode ?? '—' }}</div></div>
                <p class="text-muted small mt-3 mb-0">{{ $product->description }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-list-ul me-2 text-primary"></i>Variants (Size / Color)</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>SKU</th><th>Size</th><th>Color</th><th class="text-end">Cost</th><th class="text-end">Price</th><th class="text-end">Stock</th><th class="text-end"></th></tr></thead>
                    <tbody>
                    @forelse ($product->variants as $variant)
                        <tr>
                            <td class="small text-muted">{{ $variant->sku }}</td>
                            <td>{{ $variant->size }}</td>
                            <td>{{ $variant->color }}</td>
                            <td class="text-end small">{{ money($variant->cost_price) }}</td>
                            <td class="text-end fw-semibold">{{ money($variant->price) }}</td>
                            <td class="text-end"><span class="badge {{ $variant->total_stock > 0 ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ number_format((int) $variant->total_stock) }}</span></td>
                            <td class="text-end">
                                <form action="{{ route('variants.destroy', $variant) }}" method="POST" onsubmit="return confirm('Remove this variant?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light text-danger"><i class="bi bi-x-lg"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No variants.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><i class="bi bi-plus-circle me-2 text-success"></i>Add / Update Variant</div>
    <div class="card-body">
        <form method="POST" action="{{ route('products.variants.store', $product) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-2">
                <label class="form-label small">Size *</label>
                <input type="text" name="size" class="form-control form-control-sm" required>
                <x-field-error field="size" />
            </div>
            <div class="col-md-2">
                <label class="form-label small">Color *</label>
                <input type="text" name="color" class="form-control form-control-sm" required>
                <x-field-error field="color" />
            </div>
            <div class="col-md-2">
                <label class="form-label small">Cost price *</label>
                <input type="number" name="cost_price" value="{{ $product->cost_price }}" step="0.01" class="form-control form-control-sm" required>
                <x-field-error field="cost_price" />
            </div>
            <div class="col-md-2">
                <label class="form-label small">Retail price *</label>
                <input type="number" name="price" value="{{ $product->retail_price }}" step="0.01" class="form-control form-control-sm" required>
                <x-field-error field="price" />
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-success w-100"><i class="bi bi-save me-1"></i> Save Variant</button>
            </div>
        </form>
    </div>
</div>
@endsection
