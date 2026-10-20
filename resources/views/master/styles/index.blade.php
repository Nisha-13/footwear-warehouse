@extends('layouts.app')
@section('title', 'Styles')
@section('subtitle', 'Footwear styles and categories of design')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex gap-2" method="GET">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search styles...">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
        <a href="{{ route('styles.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Style</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Style</th><th>Description</th><th>Products</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($styles as $style)
                <tr>
                    <td class="text-muted">{{ $styles->firstItem() + $loop->index }}</td>
                    <td class="fw-semibold">{{ $style->name }}</td>
                    <td class="text-muted small">{{ Str::limit($style->description, 60) }}</td>
                    <td><span class="badge badge-soft-primary">{{ $style->products_count }}</span></td>
                    <td>{!! status_badge($style->status) !!}</td>
                    <td class="text-end">
                        <a href="{{ route('styles.edit', $style) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('styles.destroy', $style) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this style?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No styles found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($styles->hasPages())<div class="card-footer">{{ $styles->links() }}</div>@endif
</div>
@endsection
