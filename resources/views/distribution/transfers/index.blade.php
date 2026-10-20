@extends('layouts.app')
@section('title', 'Stock Transfers')
@section('subtitle', 'Inter-warehouse and warehouse-to-shop movement')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex flex-wrap gap-2" method="GET">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Transfer no...">
            <select name="direction" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All directions</option>
                <option value="warehouse_to_warehouse" @selected(request('direction') === 'warehouse_to_warehouse')>Warehouse → Warehouse</option>
                <option value="warehouse_to_shop" @selected(request('direction') === 'warehouse_to_shop')>Warehouse → Shop</option>
            </select>
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Any status</option>
                @foreach (['pending', 'assigned', 'dispatched', 'in_transit', 'delivered', 'cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
        @if (auth()->user()->isSuperAdmin() || auth()->user()->isWarehouseManager())
            <a href="{{ route('transfers.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New Transfer</a>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Transfer</th><th>Direction</th><th>From</th><th>To</th><th>Rider</th><th class="text-center">Items</th><th class="text-end">Value</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($transfers as $transfer)
                <tr>
                    <td><a href="{{ route('transfers.show', $transfer) }}" class="fw-semibold text-decoration-none">{{ $transfer->transfer_no }}</a><div class="small text-muted">{{ $transfer->created_at->format('d M Y') }}</div></td>
                    <td><span class="badge badge-soft-primary">{{ $transfer->direction === 'warehouse_to_warehouse' ? 'WH → WH' : 'WH → Shop' }}</span></td>
                    <td class="small">{{ $transfer->fromWarehouse?->name }}</td>
                    <td class="small">{{ $transfer->toWarehouse?->name ?? $transfer->toShop?->name }}</td>
                    <td class="small">{{ $transfer->rider?->name ?? '—' }}</td>
                    <td class="text-center">{{ $transfer->item_count }}</td>
                    <td class="text-end fw-semibold">{{ money($transfer->total) }}</td>
                    <td>{!! transfer_status_badge($transfer->status) !!}</td>
                    <td class="text-end"><a href="{{ route('transfers.show', $transfer) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No transfers found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($transfers->hasPages())<div class="card-footer">{{ $transfers->links() }}</div>@endif
</div>
@endsection
