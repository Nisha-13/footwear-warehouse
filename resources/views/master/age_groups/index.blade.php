@extends('layouts.app')
@section('title', 'Age Groups')
@section('subtitle', 'Month-based sizing for Kids and Babies')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-rulers me-2 text-primary"></i>Age Groups</span>
        <a href="{{ route('age-groups.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Age Group</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Name</th><th>Category</th><th>Age Range (months)</th><th>Products</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($ageGroups as $group)
                <tr>
                    <td class="text-muted">{{ $ageGroups->firstItem() + $loop->index }}</td>
                    <td class="fw-semibold">{{ $group->name }}</td>
                    <td>{{ $group->category?->name ?? '—' }}</td>
                    <td>{{ $group->min_age_months }} – {{ $group->max_age_months ?? '∞' }}</td>
                    <td><span class="badge badge-soft-primary">{{ $group->products_count }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('age-groups.edit', $group) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('age-groups.destroy', $group) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this age group?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No age groups found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($ageGroups->hasPages())<div class="card-footer">{{ $ageGroups->links() }}</div>@endif
</div>
@endsection
