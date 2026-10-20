@extends('layouts.app')
@section('title', 'Live Tracking')
@section('subtitle', 'Real-time rider locations and active deliveries')

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-map me-2 text-primary"></i>Fleet Map</div>
            <div id="fleetMap" class="wms-map" style="height:560px"></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person-badge me-2 text-primary"></i>Riders ({{ $riders->count() }})</div>
            <div class="list-group list-group-flush" style="max-height:300px;overflow:auto">
                @forelse ($riders as $rider)
                    @php $last = $locations->get($rider->id); @endphp
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">{{ $rider->name }}</div>
                            <div class="small text-muted">
                                @if ($last){{ $last->latitude }}, {{ $last->longitude }} · {{ $last->created_at->diffForHumans() }}@else No pings @endif
                            </div>
                        </div>
                        <span class="badge badge-soft-info" data-rider-active="{{ $rider->id }}">{{ $rider->deliveries->whereNotIn('status', ['delivered', 'cancelled', 'failed'])->count() }} active</span>
                    </div>
                @empty
                    <div class="list-group-item text-muted">No riders.</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span id="activeDeliveryCount"><i class="bi bi-truck me-2 text-primary"></i>Active Deliveries ({{ $activeDeliveries->count() }})</span>
                <span class="small text-muted" id="liveStatus"><i class="bi bi-broadcast text-danger"></i> Live</span>
            </div>
            <div class="list-group list-group-flush" style="max-height:300px;overflow:auto">
                @forelse ($activeDeliveries as $delivery)
                    <a href="{{ route('deliveries.show', $delivery) }}" class="list-group-item list-group-item-action" data-delivery-row="{{ $delivery->id }}">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">{{ $delivery->delivery_no }}</span>
                            <span>{!! transfer_status_badge($delivery->status) !!}</span>
                        </div>
                        <div class="small text-muted">{{ $delivery->rider?->name }} → {{ $delivery->drop_name }}</div>
                        <div class="small d-flex justify-content-between mt-1">
                            <span class="text-muted">ETA</span>
                            <span class="fw-semibold" data-eta-for="{{ $delivery->id }}">—</span>
                        </div>
                    </a>
                @empty
                    <div class="list-group-item text-muted">No active deliveries.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@php
$deliveryPoints = $activeDeliveries->map(fn ($d) => [
    'id' => $d->id,
    'no' => $d->delivery_no,
    'rider' => $d->rider?->name,
    'lat' => $d->drop_latitude,
    'lng' => $d->drop_longitude,
    'name' => $d->drop_name,
])->filter(fn ($d) => $d['lat'] && $d['lng'])->values()->all();

$riderPoints = $riders->map(function ($r) use ($locations) {
    $last = $locations->get($r->id);
    return $last ? ['id' => $r->id, 'name' => $r->name, 'lat' => $last->latitude, 'lng' => $last->longitude] : null;
})->filter()->values()->all();
@endphp

@push('styles')
@include('partials.live-map-styles')
@endpush

@push('scripts')
<script>
    const deliveryPoints = @json($deliveryPoints);
    const riderPoints = @json($riderPoints);
    const positionsUrl = @json(route('tracking.positions'));
    const POLL_MS = 5000;

    if (window.L) {
        const all = [...deliveryPoints, ...riderPoints];
        const center = all.length ? [all[0].lat, all[0].lng] : [31.4187, 73.0791];
        const map = L.map('fleetMap').setView(center, all.length ? 11 : 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19}).addTo(map);

        // Glide markers smoothly between fixes, but never during map pan/zoom.
        const mapEl = document.getElementById('fleetMap');
        mapEl.classList.add('glide-on');
        map.on('zoomstart movestart dragstart', () => mapEl.classList.remove('glide-on'));
        map.on('zoomend moveend dragend', () => mapEl.classList.add('glide-on'));

        const dropMarkers = {};   // delivery id -> marker
        const riderMarkers = {};  // rider id -> marker
        const riderDots = {};     // rider id -> inner dot element
        const links = {};         // delivery id -> polyline
        let fitted = false;       // auto-zoom once so far-apart riders and drops stay in view

        const riderIcon = () => L.divIcon({
            className: 'rider-glide',
            html: '<div class="rider-dot"></div>',
            iconSize: [16, 16],
            iconAnchor: [8, 8],
        });

        const makeRiderMarker = r => {
            const marker = L.marker([r.lat, r.lng], {
                icon: riderIcon(),
                keyboard: false,
                zIndexOffset: 1000,
            }).addTo(map).bindTooltip(`<b>${r.name}</b>`, {
                permanent: true,
                direction: 'top',
                className: 'eta-tip',
                offset: [0, -14],
                opacity: 1,
            }).bindPopup(`<b>${r.name}</b>`);
            riderMarkers[r.id] = marker;
            riderDots[r.id] = marker.getElement()?.querySelector('.rider-dot') ?? null;
            return marker;
        };

        deliveryPoints.forEach(d => {
            dropMarkers[d.id] = L.marker([d.lat, d.lng]).addTo(map).bindPopup(`<b>${d.no}</b><br>${d.name}<br>Rider: ${d.rider ?? 'Unassigned'}`);
        });

        riderPoints.forEach(makeRiderMarker);

        const mins = v => (v == null ? '—' : Math.round(v));

        const etaText = d => {
            if (d.rider_pos && d.eta_minutes != null) return `~${mins(d.eta_minutes)} min · ${d.distance_km} km`;
            if (d.stale) return 'Signal stale';
            return 'No GPS signal';
        };

        const tipText = d => {
            const name = d.rider?.name ?? 'Rider';
            if (d.stale) return `<b>${name}</b> · signal stale`;
            if (d.rider_pos && d.eta_minutes != null) return `<b>${name}</b> · ~${mins(d.eta_minutes)} min · ${d.distance_km} km`;
            return `<b>${name}</b> · no GPS signal`;
        };

        const dropReady = d => d.drop && d.drop.lat != null && d.drop.lng != null;

        const refresh = async () => {
            try {
                const res = await fetch(positionsUrl, {headers: {'Accept': 'application/json'}});
                if (!res.ok) return;
                const feed = await res.json();

                const seenDeliveries = new Set();
                const seenRiders = new Set();

                feed.deliveries.forEach(d => {
                    seenDeliveries.add(d.id);
                    if (d.rider) seenRiders.add(d.rider.id);

                    if (d.rider_pos && d.rider) {
                        const pos = [d.rider_pos.lat, d.rider_pos.lng];
                        const marker = riderMarkers[d.rider.id] ?? makeRiderMarker({id: d.rider.id, name: d.rider.name, lat: pos[0], lng: pos[1]});

                        marker.setLatLng(pos);
                        marker.setTooltipContent(tipText(d));
                        marker.setPopupContent(`<b>${d.rider.name}</b> (${d.stale ? 'signal stale' : 'live'})`);

                        const dot = riderDots[d.rider.id] ?? (riderDots[d.rider.id] = marker.getElement()?.querySelector('.rider-dot'));
                        if (dot) dot.classList.toggle('stale', !!d.stale);
                        marker.getTooltip()?.getElement()?.classList.toggle('stale', !!d.stale);
                    }

                    if (d.rider_pos && dropReady(d)) {
                        const pos = [d.rider_pos.lat, d.rider_pos.lng];
                        const drop = [d.drop.lat, d.drop.lng];

                        if (dropMarkers[d.id]) dropMarkers[d.id].setLatLng(drop);

                        if (links[d.id]) map.removeLayer(links[d.id]);
                        links[d.id] = L.polyline([pos, drop], {color: '#0d6efd', weight: 2, dashArray: '6 6'}).addTo(map);
                    }

                    const etaEl = document.querySelector(`[data-eta-for="${d.id}"]`);
                    if (etaEl) {
                        etaEl.textContent = etaText(d);
                        etaEl.classList.toggle('text-danger', !!d.stale);
                    }

                    const row = document.querySelector(`[data-delivery-row="${d.id}"]`);
                    if (row) row.classList.toggle('list-group-item-warning', !!d.stale);
                });

                // Deliveries that left the feed are finished — clear their map state and list rows.
                document.querySelectorAll('[data-delivery-row]').forEach(row => {
                    if (!seenDeliveries.has(Number(row.dataset.deliveryRow))) row.remove();
                });
                Object.keys(dropMarkers).forEach(id => {
                    if (!seenDeliveries.has(Number(id))) { map.removeLayer(dropMarkers[id]); delete dropMarkers[id]; }
                });
                Object.keys(links).forEach(id => {
                    if (!seenDeliveries.has(Number(id))) { map.removeLayer(links[id]); delete links[id]; }
                });
                // Drop feed-created rider markers for riders with no active delivery,
                // but keep riders that were placed on the map from the server-rendered list.
                Object.keys(riderMarkers).forEach(id => {
                    const riderId = Number(id);
                    if (!seenRiders.has(riderId) && !riderPoints.some(r => r.id === riderId)) {
                        map.removeLayer(riderMarkers[riderId]);
                        delete riderMarkers[riderId];
                        delete riderDots[riderId];
                    }
                });

                // First load: frame every rider and drop so nothing sits off-screen.
                if (!fitted) {
                    const pts = [];
                    Object.values(riderMarkers).forEach(m => pts.push(m.getLatLng()));
                    Object.values(dropMarkers).forEach(m => pts.push(m.getLatLng()));
                    if (pts.length > 1) map.fitBounds(L.latLngBounds(pts).pad(0.15), {animate: false});
                    if (pts.length) fitted = true;
                }

                const countEl = document.getElementById('activeDeliveryCount');
                if (countEl) countEl.innerHTML = `<i class="bi bi-truck me-2 text-primary"></i>Active Deliveries (${feed.deliveries.length})`;

                const counts = feed.rider_counts || {};
                document.querySelectorAll('[data-rider-active]').forEach(el => {
                    el.textContent = `${counts[el.dataset.riderActive] ?? 0} active`;
                });

                const status = document.getElementById('liveStatus');
                if (status) status.innerHTML = `<i class="bi bi-broadcast text-success"></i> Live · updated ${new Date().toLocaleTimeString()}`;
            } catch (e) { /* transient network failure — keep last known state */ }
        };

        refresh();
        setInterval(refresh, POLL_MS);
    }
</script>
@endpush
