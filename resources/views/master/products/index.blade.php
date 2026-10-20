@extends('layouts.app')
@section('title', 'Products')
@section('subtitle', 'Footwear catalog with sizes, colors and pricing')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex flex-wrap gap-2" method="GET">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search products / SKU...">
            <select name="brand_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All brands</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->id }}" @selected(request('brand_id') == $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
            <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
        <a href="{{ route('products.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Product</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Product</th><th>Brand</th><th>Category</th><th>Style</th><th class="text-center">Variants</th><th class="text-end">Cost</th><th class="text-end">Retail</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>
                        <a href="{{ route('products.show', $product) }}" class="fw-semibold text-decoration-none">{{ $product->name }}</a>
                        <div class="small text-muted">{{ $product->sku }}</div>
                    </td>
                    <td class="small">{{ $product->brand?->name ?? '—' }}</td>
                    <td class="small">{{ $product->category?->name ?? '—' }}</td>
                    <td class="small">{{ $product->style?->name ?? '—' }}</td>
                    <td class="text-center"><span class="badge badge-soft-info">{{ $product->variants_count }}</span></td>
                    <td class="text-end small">{{ money($product->cost_price) }}</td>
                    <td class="text-end fw-semibold">{{ money($product->retail_price) }}</td>
                    <td>{!! status_badge($product->status) !!}</td>
                    <td class="text-end">
                        <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this product and its variants?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No products found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($products->hasPages())<div class="card-footer">{{ $products->links() }}</div>@endif
</div>
@endsection
