@extends('layouts.app')
@section('title', 'Suppliers')
@section('subtitle', 'Footwear manufacturing companies and B2B vendors')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex gap-2" method="GET">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search suppliers...">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
        <a href="{{ route('suppliers.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Supplier</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Company</th><th>Contact</th><th>City</th><th class="text-center">Purchases</th><th class="text-end">Outstanding</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($suppliers as $supplier)
                <tr>
                    <td>
                        <a href="{{ route('suppliers.show', $supplier) }}" class="fw-semibold text-decoration-none">{{ $supplier->company_name }}</a>
                        <div class="small text-muted">{{ $supplier->code }}</div>
                    </td>
                    <td class="small">{{ $supplier->contact_person ?? '—' }}<br><span class="text-muted">{{ $supplier->phone }}</span></td>
                    <td class="small">{{ $supplier->city }}</td>
                    <td class="text-center"><span class="badge badge-soft-primary">{{ $supplier->purchases_count }}</span></td>
                    <td class="text-end"><span class="badge {{ $supplier->outstanding > 0 ? 'badge-soft-danger' : 'badge-soft-success' }}">{{ money($supplier->outstanding) }}</span></td>
                    <td>{!! status_badge($supplier->status) !!}</td>
                    <td class="text-end">
                        <a href="{{ route('ledger.supplier', $supplier) }}" class="btn btn-sm btn-light" title="Ledger"><i class="bi bi-journal-text"></i></a>
                        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this supplier?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No suppliers found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($suppliers->hasPages())<div class="card-footer">{{ $suppliers->links() }}</div>@endif
</div>
@endsection
