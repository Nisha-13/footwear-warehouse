@extends('layouts.app')
@section('title', 'Shop Inventory')
@section('subtitle', $shop?->display_name . ' · live stock on hand')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-boxes me-2 text-primary"></i>{{ $shop?->display_name }} Stock</span>
        <a href="{{ route('shop.sales.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-cart-plus"></i> New Sale</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Product</th><th>Variant</th><th>SKU</th><th class="text-end">On hand</th><th class="text-end">Price</th><th>Level</th></tr></thead>
            <tbody>
            @forelse ($stocks as $stock)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $stock->productVariant->product->name }}</div>
                        <div class="small text-muted">{{ $stock->productVariant->product->brand?->name ?? '—' }}</div>
                    </td>
                    <td class="small">{{ $stock->productVariant->size }} / {{ $stock->productVariant->color }}</td>
                    <td class="small text-muted">{{ $stock->productVariant->sku }}</td>
                    <td class="text-end fw-semibold">{{ number_format($stock->quantity) }}</td>
                    <td class="text-end">{{ money($stock->productVariant->price) }}</td>
                    <td style="min-width:110px">
                        @php $pct = min(100, $stock->reorder_level > 0 ? ($stock->quantity / max(1, $stock->reorder_level * 3)) * 100 : 100); @endphp
                        <div class="progress" style="height:6px"><div class="progress-bar {{ $stock->quantity <= $stock->reorder_level ? 'bg-danger' : 'bg-success' }}" style="width:{{ $pct }}%"></div></div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No stock available.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($stocks->hasPages())<div class="card-footer">{{ $stocks->links() }}</div>@endif
</div>
@endsection
