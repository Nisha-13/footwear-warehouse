@extends('layouts.app')
@section('title', 'Stock Movements')
@section('subtitle', 'Audit trail of all inventory changes')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2 text-primary"></i>Movement History</span>
        <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i> Back to Inventory</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Date</th><th>Product</th><th>Variant</th><th>Location</th><th>Type</th><th class="text-end">Change</th><th class="text-end">Balance</th><th>By</th></tr></thead>
            <tbody>
            @forelse ($movements as $m)
                <tr>
                    <td class="small text-muted">{{ $m->created_at->format('d M Y H:i') }}</td>
                    <td class="small fw-semibold">{{ $m->productVariant->product->name }}</td>
                    <td class="small">{{ $m->productVariant->size }} / {{ $m->productVariant->color }}</td>
                    <td class="small">{{ $m->stockable?->name ?? '—' }}</td>
                    <td>{!! status_badge($m->type) !!}</td>
                    <td class="text-end fw-semibold {{ $m->quantity >= 0 ? 'text-success' : 'text-danger' }}">{{ $m->quantity >= 0 ? '+' : '' }}{{ $m->quantity }}</td>
                    <td class="text-end">{{ $m->balance_after }}</td>
                    <td class="small text-muted">{{ $m->user?->name ?? 'System' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No movements recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($movements->hasPages())<div class="card-footer">{{ $movements->links() }}</div>@endif
</div>
@endsection
