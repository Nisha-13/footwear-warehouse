@extends('layouts.app')
@section('title', 'Inventory')
@section('subtitle', 'Real-time stock levels across warehouses and shops')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex flex-wrap gap-2" method="GET">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search product / SKU / size / color...">
            @if (auth()->user()->isSuperAdmin())
                <select name="stockable_type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All locations</option>
                    <option value="warehouse" @selected(request('stockable_type') === 'warehouse')>Warehouses</option>
                    <option value="shop" @selected(request('stockable_type') === 'shop')>Shops</option>
                </select>
            @endif
            <label class="btn btn-sm btn-outline-danger @if(request('low_stock')) active @endif">
                <input type="checkbox" name="low_stock" value="1" class="d-none" onchange="this.form.submit()" @checked(request('low_stock'))>
                <i class="bi bi-exclamation-triangle"></i> Low stock
            </label>
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
        <a href="{{ route('inventory.movements') }}" class="btn btn-sm btn-light"><i class="bi bi-clock-history"></i> Movement History</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Product</th><th>Variant</th><th>Location</th><th class="text-end">On hand</th><th class="text-end">Reorder</th><th>Level</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            @forelse ($stocks as $stock)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $stock->productVariant->product->name }}</div>
                        <div class="small text-muted">{{ $stock->productVariant->product->brand?->name ?? '—' }}</div>
                    </td>
                    <td class="small">{{ $stock->productVariant->size }} / {{ $stock->productVariant->color }}<div class="text-muted">{{ $stock->productVariant->sku }}</div></td>
                    <td class="small">{{ $stock->stockable?->name ?? '—' }}</td>
                    <td class="text-end fw-semibold">{{ number_format($stock->quantity) }}</td>
                    <td class="text-end text-muted">{{ $stock->reorder_level }}</td>
                    <td style="min-width:120px">
                        @php $pct = min(100, $stock->reorder_level > 0 ? ($stock->quantity / max(1, $stock->reorder_level * 3)) * 100 : 100); @endphp
                        <div class="progress" style="height:6px">
                            <div class="progress-bar {{ $stock->quantity <= $stock->reorder_level ? 'bg-danger' : ($pct < 60 ? 'bg-warning' : 'bg-success') }}" style="width:{{ $pct }}%"></div>
                        </div>
                    </td>
                    <td class="text-end">
                        @if (auth()->user()->isSuperAdmin() || auth()->user()->isWarehouseManager())
                            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#adjustModal"
                                data-action="{{ route('inventory.adjust', $stock) }}"
                                data-label="{{ $stock->productVariant->product->name }} — {{ $stock->productVariant->size }}/{{ $stock->productVariant->color }}"
                                data-qty="{{ $stock->quantity }}"><i class="bi bi-sliders"></i></button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No inventory records.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($stocks->hasPages())<div class="card-footer">{{ $stocks->links() }}</div>@endif
</div>

<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST" id="adjustForm">
            @csrf
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-sliders me-2"></i>Adjust Stock</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small text-muted mb-3" id="adjustLabel"></p>
                <label class="form-label">New on-hand quantity *</label>
                <input type="number" name="quantity" id="adjustQty" class="form-control" min="0" required>
                <x-field-error field="quantity" />
                <label class="form-label mt-3">Reason / Note</label>
                <input type="text" name="note" class="form-control" placeholder="e.g. Damaged, stock count correction">
                <x-field-error field="note" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Apply Adjustment</button>
            </div>
        </form>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
    const adjustModal = document.getElementById('adjustModal');
    adjustModal.addEventListener('show.bs.modal', e => {
        const btn = e.relatedTarget;
        document.getElementById('adjustForm').action = btn.dataset.action;
        document.getElementById('adjustLabel').textContent = btn.dataset.label;
        document.getElementById('adjustQty').value = btn.dataset.qty;
    });
</script>
@endpush
