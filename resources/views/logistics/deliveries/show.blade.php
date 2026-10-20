@extends('layouts.app')
@section('title', 'Delivery ' . $delivery->delivery_no)
@section('subtitle', $delivery->transfer?->fromWarehouse?->name . ' → ' . ($delivery->drop_name ?? $delivery->transfer?->toShop?->name ?? $delivery->transfer?->toWarehouse?->name))

@section('content')
@php $user = auth()->user(); @endphp

<div class="row g-3 mb-3">
    <div class="col-lg-3"><x-stat label="Status" :value="ucwords(str_replace('_', ' ', $delivery->status))" icon="bi-truck" tone="primary" /></div>
    <div class="col-lg-3"><x-stat label="Rider" :value="$delivery->rider?->name ?? 'Unassigned'" icon="bi-person-badge" tone="info" /></div>
    <div class="col-lg-3"><x-stat label="Payment Mode" :value="ucwords(str_replace('_', ' ', $delivery->payment_mode ?? '—'))" icon="bi-credit-card" tone="warning" /></div>
    <div class="col-lg-3"><x-stat label="Transaction Amount" :value="money($delivery->transaction_amount)" icon="bi-cash-stack" tone="success" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-box-seam me-2 text-primary"></i>Items</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Product</th><th>Variant</th><th class="text-end">Qty</th></tr></thead>
                    <tbody>
                    @forelse ($delivery->transfer?->items ?? [] as $item)
                        <tr>
                            <td class="fw-semibold">{{ $item->productVariant?->product?->name }}</td>
                            <td class="small">{{ $item->productVariant?->size }}/{{ $item->productVariant?->color }}</td>
                            <td class="text-end">{{ $item->quantity }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No items.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($user->isRider() || $user->isSuperAdmin() || $user->isWarehouseManager())
        @if ($delivery->isTerminal())
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-lock me-2 text-secondary"></i>Status Finalized</div>
            <div class="card-body">
                <div class="alert alert-secondary mb-0">
                    This delivery was marked <strong>{{ $delivery->status_label }}</strong>
                    on {{ optional($delivery->delivered_at ?? $delivery->updated_at)->format('d M Y H:i') }}
                    and can no longer be updated.
                    @if ($delivery->status === 'cancelled' && $delivery->failure_reason)
                        <div class="small mt-2"><span class="fw-semibold">Cancellation reason:</span> {{ $delivery->failure_reason }}</div>
                    @endif
                </div>
            </div>
        </div>
        @else
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-arrow-repeat me-2 text-primary"></i>Update Status</div>
            <div class="card-body">
                @if ($delivery->status === 'failed' && $delivery->failure_reason)
                    <div class="alert alert-warning py-2 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Last attempt failed — <strong>{{ $delivery->failure_reason_label }}</strong>.
                        Retry the delivery or cancel it.
                    </div>
                @endif
                <form method="POST" action="{{ route('deliveries.status', $delivery) }}" id="statusForm" class="row g-2">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label small">New status</label>
                        <select name="status" class="form-select form-select-sm" id="statusSelect" required>
                            @foreach ($delivery->allowedNextStatuses() as $s)
                                <option value="{{ $s }}" @selected(old('status') === $s)>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                            @endforeach
                        </select>
                        <x-field-error field="status" />
                    </div>
                    <div class="col-md-4 d-none" id="failureReasonCol">
                        <label class="form-label small">Failure reason *</label>
                        <select name="failure_code" class="form-select form-select-sm" id="failureCodeSelect">
                            <option value="">— Select reason —</option>
                            @foreach (\App\Models\Delivery::FAILURE_REASONS as $code => $label)
                                <option value="{{ $code }}" @selected(old('failure_code') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-field-error field="failure_code" />
                    </div>
                    <div class="col-md-4" id="noteCol">
                        <label class="form-label small" id="noteLabel">Note</label>
                        <input type="text" name="note" id="noteInput" class="form-control form-control-sm" value="{{ old('note') }}" placeholder="Optional note">
                        <x-field-error field="note" />
                    </div>
                    <div class="col-md-4">
                        <input type="hidden" name="latitude" id="lat">
                        <input type="hidden" name="longitude" id="lng">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="useLocation"><i class="bi bi-geo-alt"></i> Use GPS</button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-sm btn-primary"><i class="bi bi-check-lg"></i> Update Status</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
        @endif

        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-2 text-primary"></i>Status Timeline</div>
            <div class="card-body">
                <div class="wms-timeline">
                    @forelse ($delivery->statusHistories->sortByDesc('created_at') as $history)
                        <div class="wms-timeline-item">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">{!! transfer_status_badge($history->status) !!}</span>
                                <span class="small text-muted">{{ $history->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <div class="small text-muted">{{ $history->note }} · {{ $history->user?->name ?? 'System' }}</div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No status history.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        @if (! $delivery->isTerminal() && $delivery->rider_id)
        <div class="card mb-3" id="liveEtaCard"
             data-position-url="{{ route('deliveries.position', $delivery) }}"
             data-delivery-id="{{ $delivery->id }}"
             data-share="{{ auth()->id() === $delivery->rider_id ? 1 : 0 }}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-broadcast me-2 text-danger"></i>Live ETA</span>
                <span class="small text-muted" id="etaFreshness">connecting…</span>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline">
                    <div class="fs-4 fw-bold" id="etaText">—</div>
                    <div class="small text-muted" id="etaDistance"></div>
                </div>
                <div class="small text-muted mt-1" id="etaMeta">Waiting for the rider's GPS…</div>
            </div>
        </div>
        @endif

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-geo-alt me-2 text-primary"></i>Drop Location</div>
            <div class="card-body small">
                <div class="mb-2"><span class="text-muted">Name</span><div class="fw-semibold">{{ $delivery->drop_name ?? '—' }}</div></div>
                <div class="mb-2"><span class="text-muted">Address</span><div class="fw-semibold">{{ $delivery->drop_address ?? '—' }}</div></div>
                <div class="mb-2"><span class="text-muted">Coordinates</span><div class="fw-semibold">{{ $delivery->drop_latitude ?? '—' }}, {{ $delivery->drop_longitude ?? '—' }}</div></div>
            </div>
            <div id="deliveryMap" class="wms-map" style="height:280px"></div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-pin-map me-2 text-primary"></i>Recent Rider Pings</div>
            <div class="list-group list-group-flush small">
                @forelse ($delivery->locations as $loc)
                    <div class="list-group-item d-flex justify-content-between">
                        <span>{{ $loc->latitude }}, {{ $loc->longitude }}</span>
                        <span class="text-muted">{{ $loc->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <div class="list-group-item text-muted">No location pings yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@php
$dropPoint = [$delivery->drop_latitude, $delivery->drop_longitude];
$pingPoints = $delivery->locations->map(fn ($l) => [$l->latitude, $l->longitude])->values()->all();
@endphp

@push('styles')
@include('partials.live-map-styles')
@endpush

@push('scripts')
<script>
    const dl = @json($dropPoint);
    const pings = @json($pingPoints);
    let map, riderMarker;

    if (dl[0] && dl[1] && window.L) {
        map = L.map('deliveryMap').setView(dl, 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19}).addTo(map);
        L.marker(dl).addTo(map).bindPopup('Drop point').openPopup();
        if (pings.length) {
            L.polyline([dl, ...pings], {color: '#0d6efd'}).addTo(map);
            map.fitBounds(L.latLngBounds([dl, ...pings]).pad(0.2), {animate: false});
        }
    }

    // Live rider marker — same animated dot as the fleet map.
    const mapEl = document.getElementById('deliveryMap');
    const riderIcon = () => L.divIcon({
        className: 'rider-glide',
        html: '<div class="rider-dot"></div>',
        iconSize: [16, 16],
        iconAnchor: [8, 8],
    });

    if (map && mapEl) {
        mapEl.classList.add('glide-on');
        map.on('zoomstart movestart dragstart', () => mapEl.classList.remove('glide-on'));
        map.on('zoomend moveend dragend', () => mapEl.classList.add('glide-on'));
    }

    const updateRiderMarker = d => {
        if (!map || !d.rider_pos) return;

        const pos = [d.rider_pos.lat, d.rider_pos.lng];
        const name = d.rider?.name ?? 'Rider';
        const tip = d.stale
            ? `<b>${name}</b> · signal stale`
            : (d.eta_minutes != null
                ? `<b>${name}</b> · ~${Math.round(d.eta_minutes)} min · ${d.distance_km} km`
                : `<b>${name}</b> · live`);

        if (!riderMarker) {
            riderMarker = L.marker(pos, {icon: riderIcon(), keyboard: false, zIndexOffset: 1000})
                .addTo(map)
                .bindTooltip(tip, {permanent: true, direction: 'top', className: 'eta-tip', offset: [0, -14], opacity: 1});
            if (!map.getBounds().contains(riderMarker.getLatLng())) {
                map.fitBounds(L.latLngBounds([pos, dl]).pad(0.2), {animate: false});
            }
        } else {
            riderMarker.setLatLng(pos);
            riderMarker.setTooltipContent(tip);
        }

        riderMarker.getTooltip()?.getElement()?.classList.toggle('stale', !!d.stale);
        const dot = riderMarker.getElement()?.querySelector('.rider-dot');
        if (dot) dot.classList.toggle('stale', !!d.stale);
    };

    const gps = document.getElementById('useLocation');
    if (gps) {
        gps.addEventListener('click', () => {
            navigator.geolocation.getCurrentPosition(pos => {
                document.getElementById('lat').value = pos.coords.latitude;
                document.getElementById('lng').value = pos.coords.longitude;
                gps.classList.add('btn-success');
                gps.innerHTML = '<i class="bi bi-check-lg"></i> GPS';
            });
        });
    }

    const statusForm = document.getElementById('statusForm');
    const statusSelect = document.getElementById('statusSelect');
    const failureReasonCol = document.getElementById('failureReasonCol');
    const failureCodeSelect = document.getElementById('failureCodeSelect');
    const noteInput = document.getElementById('noteInput');
    const noteLabel = document.getElementById('noteLabel');

    if (statusForm && statusSelect) {
        const deliveryNo = @json($delivery->delivery_no);

        const syncForm = () => {
            const status = statusSelect.value;
            const failing = status === 'failed';
            const cancelling = status === 'cancelled';

            if (failureReasonCol && failureCodeSelect) {
                failureReasonCol.classList.toggle('d-none', !failing);
                failureCodeSelect.required = failing;
            }

            if (noteInput && noteLabel) {
                if (cancelling) {
                    noteLabel.textContent = 'Cancellation reason *';
                    noteInput.required = true;
                    noteInput.placeholder = 'Why is this order being cancelled?';
                } else if (failing) {
                    noteLabel.textContent = 'Comment';
                    noteInput.required = failureCodeSelect && failureCodeSelect.value === 'other';
                    noteInput.placeholder = 'Details (required for "Other")';
                } else {
                    noteLabel.textContent = 'Note';
                    noteInput.required = false;
                    noteInput.placeholder = 'Optional note';
                }
            }
        };

        statusSelect.addEventListener('change', syncForm);
        if (failureCodeSelect) failureCodeSelect.addEventListener('change', syncForm);
        syncForm();

        statusForm.addEventListener('submit', event => {
            const label = statusSelect.options[statusSelect.selectedIndex].textContent.trim();
            let message = 'Update delivery ' + deliveryNo + ' status to "' + label + '"?';

            if (statusSelect.value === 'delivered') {
                message += '\n\nMarking as Delivered is final and cannot be undone.';
            } else if (statusSelect.value === 'cancelled') {
                message += '\n\nA cancellation reason is required. Marking as Cancelled is final and cannot be undone.';
            } else if (statusSelect.value === 'failed') {
                message += '\n\nA failure reason is required. You can retry the delivery afterwards.';
            }

            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    }

    const startLiveEtaPolling = () => {
        const etaCard = document.getElementById('liveEtaCard');
        if (!etaCard) return;

        const positionUrl = etaCard.dataset.positionUrl;
        const fresh = document.getElementById('etaFreshness');
        const text = document.getElementById('etaText');
        const dist = document.getElementById('etaDistance');
        const meta = document.getElementById('etaMeta');
        let finalized = false;

        const timeLabel = iso => iso ? new Date(iso).toLocaleTimeString() : '';

        const render = d => {
            updateRiderMarker(d);

            if (d.stale) {
                fresh.innerHTML = '<i class="bi bi-broadcast text-warning"></i> Signal stale';
                text.textContent = '—';
                dist.textContent = '';
                meta.textContent = `Last seen ${timeLabel(d.last_seen) || 'a while ago'} — waiting for a fresh GPS fix.`;
            } else if (d.rider_pos && d.eta_minutes != null) {
                fresh.innerHTML = `<i class="bi bi-broadcast text-danger"></i> Live · updated ${new Date().toLocaleTimeString()}`;
                text.textContent = `~${d.eta_minutes} min`;
                dist.textContent = d.distance_km != null ? `${d.distance_km} km away` : '';
                const bits = [d.rider?.name ?? 'Rider'];
                if (d.speed_kmh != null) bits.push(`${d.speed_kmh} km/h`);
                meta.textContent = bits.join(' · ');
            } else {
                fresh.innerHTML = '<i class="bi bi-broadcast text-secondary"></i> No GPS';
                text.textContent = '—';
                dist.textContent = '';
                meta.textContent = "Waiting for the rider's GPS signal…";
            }
        };

        const poll = async () => {
            if (finalized) return;
            try {
                const res = await fetch(positionUrl, {headers: {'Accept': 'application/json'}});
                if (!res.ok) return;
                const d = await res.json();
                render(d);
                if (d.terminal) {
                    finalized = true;
                    fresh.innerHTML = '<i class="bi bi-lock text-secondary"></i> Finalized';
                }
            } catch (e) { /* transient network failure — keep last known state */ }
        };

        poll();
        setInterval(poll, 5000);
    };
    startLiveEtaPolling();
</script>
@endpush

@if (auth()->id() === $delivery->rider_id && ! $delivery->isTerminal())
    @include('partials.live-tracking', ['liveDeliveryId' => $delivery->id])
@endif
