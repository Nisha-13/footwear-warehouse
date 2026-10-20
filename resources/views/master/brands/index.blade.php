@extends('layouts.app')
@section('title', 'Brands')
@section('subtitle', 'Footwear manufacturers and sub-brands')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex gap-2" method="GET">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search brands...">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
        <a href="{{ route('brands.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Brand</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Brand</th><th>Sub-brand</th><th>Country</th><th>Products</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($brands as $brand)
                <tr>
                    <td class="text-muted">{{ $brands->firstItem() + $loop->index }}</td>
                    <td class="fw-semibold">{{ $brand->name }}</td>
                    <td>{{ $brand->sub_brand ?? '—' }}</td>
                    <td>{{ $brand->country ?? '—' }}</td>
                    <td><span class="badge badge-soft-primary">{{ $brand->products_count }}</span></td>
                    <td>{!! status_badge($brand->status) !!}</td>
                    <td class="text-end">
                        <a href="{{ route('brands.edit', $brand) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('brands.destroy', $brand) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this brand?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No brands found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($brands->hasPages())<div class="card-footer">{{ $brands->links() }}</div>@endif
</div>
@endsection
