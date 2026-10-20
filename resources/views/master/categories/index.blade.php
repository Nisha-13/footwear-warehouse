@extends('layouts.app')
@section('title', 'Categories')
@section('subtitle', "Men's, Women's, Kid's and Babies with age-based sub-categories")

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-diagram-3 me-2 text-primary"></i>Product Categories</span>
        <a href="{{ route('categories.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Category</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Category</th><th>Parent</th><th>Gender</th><th>Age Groups</th><th>Products</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($categories as $category)
                <tr>
                    <td class="text-muted">{{ $categories->firstItem() + $loop->index }}</td>
                    <td><a href="{{ route('categories.show', $category) }}" class="fw-semibold text-decoration-none">{{ $category->name }}</a></td>
                    <td class="text-muted">{{ $category->parent?->name ?? '—' }}</td>
                    <td><span class="badge badge-soft-info">{{ ucfirst($category->gender) }}</span></td>
                    <td>{{ $category->age_groups_count }}</td>
                    <td><span class="badge badge-soft-primary">{{ $category->products_count }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this category?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No categories found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($categories->hasPages())<div class="card-footer">{{ $categories->links() }}</div>@endif
</div>
@endsection
