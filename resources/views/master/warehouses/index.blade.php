@extends('layouts.app')
@section('title', 'Warehouses')
@section('subtitle', 'Main branch and regional hubs across Pakistan')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex gap-2" method="GET">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search warehouses...">
            <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All types</option>
                <option value="main" @selected(request('type') === 'main')>Main</option>
                <option value="regional" @selected(request('type') === 'regional')>Regional</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
        <a href="{{ route('warehouses.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Warehouse</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Warehouse</th><th>Type</th><th>City</th><th>Manager</th><th class="text-center">Shops</th><th class="text-end">Stock</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($warehouses as $warehouse)
                <tr>
                    <td>
                        <a href="{{ route('warehouses.show', $warehouse) }}" class="fw-semibold text-decoration-none">{{ $warehouse->name }}</a>
                        <div class="small text-muted">{{ $warehouse->code }}</div>
                    </td>
                    <td>
                        @if ($warehouse->type === 'main')
                            <span class="badge badge-soft-primary"><i class="bi bi-star-fill me-1"></i>Main</span>
                        @else
                            <span class="badge badge-soft-info">Regional</span>
                        @endif
                    </td>
                    <td>{{ $warehouse->city }}</td>
                    <td class="small">{{ $warehouse->manager?->name ?? '—' }}</td>
                    <td class="text-center">{{ $warehouse->shops_count }}</td>
                    <td class="text-end fw-semibold">{{ number_format((int) $warehouse->stock_units) }}</td>
                    <td>{!! status_badge($warehouse->status) !!}</td>
                    <td class="text-end">
                        <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('warehouses.destroy', $warehouse) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this warehouse?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No warehouses found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($warehouses->hasPages())<div class="card-footer">{{ $warehouses->links() }}</div>@endif
</div>
@endsection
