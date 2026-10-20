@extends('layouts.app')
@section('title', 'My Deliveries')
@section('subtitle', 'All deliveries assigned to you')

@section('content')
<div class="card">
    <div class="card-header"><i class="bi bi-truck me-2 text-primary"></i>Deliveries</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Delivery</th><th>Drop</th><th>Address</th><th>Payment</th><th class="text-end">Amount</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($deliveries as $delivery)
                <tr>
                    <td class="fw-semibold">{{ $delivery->delivery_no }}<div class="small text-muted">{{ $delivery->created_at->format('d M Y') }}</div></td>
                    <td class="small">{{ $delivery->drop_name ?? '—' }}</td>
                    <td class="small text-muted">{{ $delivery->drop_address }}</td>
                    <td class="small">{{ ucwords(str_replace('_', ' ', $delivery->payment_mode ?? '—')) }}</td>
                    <td class="text-end fw-semibold">{{ money($delivery->transaction_amount) }}</td>
                    <td>{!! transfer_status_badge($delivery->status) !!}</td>
                    <td class="text-end"><a href="{{ route('deliveries.show', $delivery) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No deliveries assigned.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($deliveries->hasPages())<div class="card-footer">{{ $deliveries->links() }}</div>@endif
</div>
@endsection

@include('partials.live-tracking')
