@extends('layouts.app')
@section('title', 'Transfer ' . $transfer->transfer_no)
@section('subtitle', $transfer->fromWarehouse?->name . ' → ' . ($transfer->toWarehouse?->name ?? $transfer->toShop?->name))

@section('content')
@php $user = auth()->user(); @endphp

<div class="row g-3 mb-3">
    <div class="col-lg-3"><x-stat label="Items" :value="$transfer->item_count" icon="bi-boxes" tone="primary" :hint="$transfer->items->count().' line items'" /></div>
    <div class="col-lg-3"><x-stat label="Transfer Value" :value="money($transfer->total)" icon="bi-cash-stack" tone="success" :hint="'Shipping '.money($transfer->shipping_cost)" /></div>
    <div class="col-lg-3"><x-stat label="Rider" :value="$transfer->rider?->name ?? 'Unassigned'" icon="bi-person-badge" tone="info" /></div>
    <div class="col-lg-3"><x-stat label="Status" :value="ucwords(str_replace('_', ' ', $transfer->status))" icon="bi-truck" tone="warning" :hint="$transfer->expected_date ? 'Expected '.$transfer->expected_date->format('d M Y') : null" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-box-seam me-2 text-primary"></i>Items</span>
                <span>{!! transfer_status_badge($transfer->status) !!}</span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Product</th><th>Variant</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @foreach ($transfer->items as $item)
                        <tr>
                            <td class="fw-semibold">{{ $item->productVariant?->product?->name }}</td>
                            <td class="small">{{ $item->productVariant?->size }}/{{ $item->productVariant?->color }}<div class="text-muted">{{ $item->productVariant?->sku }}</div></td>
                            <td class="text-end">{{ $item->quantity }}</td>
                            <td class="text-end">{{ money($item->unit_price) }}</td>
                            <td class="text-end fw-semibold">{{ money($item->total) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td colspan="4" class="text-end text-muted">Subtotal</td><td class="text-end">{{ money($transfer->subtotal) }}</td></tr>
                        <tr><td colspan="4" class="text-end text-muted">Shipping</td><td class="text-end">{{ money($transfer->shipping_cost) }}</td></tr>
                        <tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td class="text-end">{{ money($transfer->total) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if ($transfer->deliveries->count())
        <div class="card">
            <div class="card-header"><i class="bi bi-truck me-2 text-primary"></i>Deliveries</div>
            <div class="list-group list-group-flush">
                @foreach ($transfer->deliveries as $delivery)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">{{ $delivery->delivery_no }} · {{ $delivery->rider?->name }}</div>
                            <div class="small text-muted">{{ ucfirst($delivery->status) }} · {{ $delivery->created_at->format('d M Y H:i') }}</div>
                        </div>
                        <a href="{{ route('deliveries.show', $delivery) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle me-2 text-primary"></i>Details</div>
            <div class="card-body small">
                <div class="mb-2"><span class="text-muted">Payment mode</span><div class="fw-semibold">{{ ucwords(str_replace('_', ' ', $transfer->payment_mode)) }}</div></div>
                <div class="mb-2"><span class="text-muted">Created by</span><div class="fw-semibold">{{ $transfer->creator?->name ?? 'System' }}</div></div>
                <div class="mb-2"><span class="text-muted">Created</span><div class="fw-semibold">{{ $transfer->created_at->format('d M Y H:i') }}</div></div>
                @if ($transfer->notes)<div class="mb-2"><span class="text-muted">Notes</span><div>{{ $transfer->notes }}</div></div>@endif
            </div>
        </div>

        @if ($user->isSuperAdmin() || $user->isWarehouseManager())
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-gear me-2 text-primary"></i>Actions</div>
            <div class="card-body d-grid gap-2">
                @if (in_array($transfer->status, ['pending', 'assigned']))
                    <form method="POST" action="{{ route('transfers.assign', $transfer) }}" class="d-flex gap-2">
                        @csrf
                        <div class="flex-grow-1">
                            <select name="rider_id" class="form-select form-select-sm">
                                @foreach ($riders as $rider)
                                    <option value="{{ $rider->id }}" @selected($transfer->rider_id == $rider->id)>{{ $rider->name }}</option>
                                @endforeach
                            </select>
                            <x-field-error field="rider_id" />
                        </div>
                        <button class="btn btn-sm btn-primary text-nowrap"><i class="bi bi-person-plus"></i> Assign</button>
                    </form>
                @endif

                @if ($transfer->status === 'assigned')
                    <form method="POST" action="{{ route('transfers.dispatch', $transfer) }}">
                        @csrf
                        <button class="btn btn-sm btn-warning w-100"><i class="bi bi-send"></i> Dispatch Transfer</button>
                    </form>
                @endif

                @if ($transfer->status === 'dispatched' && $user->isWarehouseManager())
                    <form method="POST" action="{{ route('transfers.deliver', $transfer) }}">
                        @csrf
                        <button class="btn btn-sm btn-success w-100"><i class="bi bi-check-lg"></i> Mark Received</button>
                    </form>
                @endif

                @if (in_array($transfer->status, ['pending', 'assigned']))
                    <form method="POST" action="{{ route('transfers.cancel', $transfer) }}" onsubmit="return confirm('Cancel this transfer?')">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-x-circle"></i> Cancel Transfer</button>
                    </form>
                    <form method="POST" action="{{ route('transfers.destroy', $transfer) }}" onsubmit="return confirm('Delete this transfer?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-trash"></i> Delete</button>
                    </form>
                @endif
            </div>
        </div>
        @endif

        @if ($user->isShopkeeper() && $transfer->to_shop_id === $user->shop_id && $transfer->status === 'dispatched')
            <div class="card mb-3">
                <div class="card-body">
                    <form method="POST" action="{{ route('transfers.deliver', $transfer) }}">
                        @csrf
                        <button class="btn btn-success w-100"><i class="bi bi-check-lg"></i> Confirm Receipt</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
