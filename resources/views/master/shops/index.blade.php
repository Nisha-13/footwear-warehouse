@extends('layouts.app')
@section('title', auth()->user()->isWarehouseManager() ? 'My Shops' : 'Retail Shops')
@section('subtitle', 'Local retail network supplied by regional warehouses')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex flex-wrap gap-2" method="GET">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search shops...">
            @if (auth()->user()->isSuperAdmin())
                <select name="warehouse_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All warehouses</option>
                    @foreach ($warehouses as $w)
                        <option value="{{ $w->id }}" @selected(request('warehouse_id') == $w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>
            @endif
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
        @if (auth()->user()->isSuperAdmin())
            <a href="{{ route('shops.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Shop</a>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Shop</th><th>Warehouse</th><th>Owner</th><th>Shopkeeper</th><th class="text-end">Stock</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($shops as $shop)
                <tr>
                    <td>
                        <a href="{{ route('shops.show', $shop) }}" class="fw-semibold text-decoration-none">{{ $shop->display_name }}</a>
                        <div class="small text-muted">{{ $shop->code }} · {{ $shop->city }}</div>
                    </td>
                    <td class="small">{{ $shop->warehouse?->name }}</td>
                    <td class="small">{{ $shop->owner_name }}</td>
                    <td class="small">{{ $shop->shopkeeper?->name ?? '—' }}</td>
                    <td class="text-end fw-semibold">{{ number_format((int) $shop->stock_units) }}</td>
                    <td>{!! status_badge($shop->status) !!}</td>
                    <td class="text-end">
                        @if (auth()->user()->isSuperAdmin())
                            <a href="{{ route('shops.edit', $shop) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('shops.destroy', $shop) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this shop?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        @else
                            <a href="{{ route('shops.show', $shop) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No shops found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($shops->hasPages())<div class="card-footer">{{ $shops->links() }}</div>@endif
</div>
@endsection
