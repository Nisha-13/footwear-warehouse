@extends('layouts.app')
@section('title', $category->name)
@section('subtitle', ucfirst($category->gender) . ' footwear category')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-diagram-3 me-2 text-primary"></i>Category Details</div>
            <div class="card-body">
                <p class="mb-1 fw-semibold">{{ $category->name }}</p>
                <p class="text-muted small mb-3">{{ $category->description }}</p>
                <div class="mb-2"><span class="text-muted small">Parent:</span> {{ $category->parent?->name ?? 'Top level' }}</div>
                <div class="mb-3"><span class="text-muted small">Gender:</span> <span class="badge badge-soft-info">{{ ucfirst($category->gender) }}</span></div>
                <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-rulers me-2 text-info"></i>Age Groups</div>
            <ul class="list-group list-group-flush">
                @forelse ($category->ageGroups as $group)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $group->name }}</span>
                        <span class="text-muted small">{{ $group->min_age_months }}–{{ $group->max_age_months ?? '∞' }} months</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted small">No age groups.</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-shoe-prints me-2 text-primary"></i>Products in this Category ({{ $products->total() }})</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Product</th><th>Brand</th><th>Style</th><th class="text-end">Retail</th></tr></thead>
                    <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td><a href="{{ route('products.show', $product) }}" class="text-decoration-none fw-semibold">{{ $product->name }}</a><div class="small text-muted">{{ $product->sku }}</div></td>
                            <td>{{ $product->brand?->name ?? '—' }}</td>
                            <td>{{ $product->style?->name ?? '—' }}</td>
                            <td class="text-end fw-semibold">{{ money($product->retail_price) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No products yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($products->hasPages())<div class="card-footer">{{ $products->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
