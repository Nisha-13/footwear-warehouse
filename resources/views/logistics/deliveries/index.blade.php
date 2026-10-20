@extends('layouts.app')
@section('title', 'Deliveries')
@section('subtitle', 'Dispatch tracking and rider assignments')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex flex-wrap gap-2" method="GET">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Delivery no...">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Any status</option>
                @foreach (['assigned', 'picked_up', 'dispatched', 'in_transit', 'delivered', 'failed', 'cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
            <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Any type</option>
                <option value="delivery" @selected(request('type') === 'delivery')>Delivery</option>
                <option value="pickup" @selected(request('type') === 'pickup')>Pickup</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Delivery</th><th>Route</th><th>Rider</th><th>Drop</th><th>Payment</th><th class="text-end">Amount</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($deliveries as $delivery)
                <tr>
                    <td><a href="{{ route('deliveries.show', $delivery) }}" class="fw-semibold text-decoration-none">{{ $delivery->delivery_no }}</a><div class="small text-muted">{{ $delivery->created_at->format('d M Y') }}</div></td>
                    <td class="small">
                        {{ $delivery->transfer?->fromWarehouse?->name }} →<br>
                        {{ $delivery->transfer?->toShop?->name ?? $delivery->transfer?->toWarehouse?->name }}
                    </td>
                    <td class="small">{{ $delivery->rider?->name ?? '—' }}</td>
                    <td class="small">{{ $delivery->drop_name ?? '—' }}<div class="text-muted">{{ $delivery->drop_address }}</div></td>
                    <td class="small">{{ ucwords(str_replace('_', ' ', $delivery->payment_mode ?? '—')) }}</td>
                    <td class="text-end fw-semibold">{{ money($delivery->transaction_amount) }}</td>
                    <td>{!! transfer_status_badge($delivery->status) !!}</td>
                    <td class="text-end"><a href="{{ route('deliveries.show', $delivery) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No deliveries found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($deliveries->hasPages())<div class="card-footer">{{ $deliveries->links() }}</div>@endif
</div>
@endsection
