@extends('layouts.app')
@section('title', 'My Route')
@section('subtitle', 'Optimised delivery sequence')

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-map me-2 text-primary"></i>Route Map</div>
            <div id="routeMap" class="wms-map" style="height:560px"></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-signpost-2 me-2 text-primary"></i>Optimised Stops ({{ count($optimized) }})</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="useGps"><i class="bi bi-geo-alt"></i> My GPS</button>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($optimized as $i => $stop)
                    <a href="{{ route('deliveries.show', $stop['id']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold"><span class="badge bg-primary me-1">{{ $i + 1 }}</span>{{ $stop['name'] }}</div>
                            <div class="small text-muted">{{ $stop['address'] }}</div>
                        </div>
                        {!! transfer_status_badge($stop['status']) !!}
                    </a>
                @empty
                    <div class="list-group-item text-muted text-center py-4">No stops assigned.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const origin = @json($origin);
    const stops = @json($optimized);
    let map, routeLayer, meMarker;

    if (window.L) {
        const valid = stops.filter(s => s.latitude && s.longitude);
        const start = (origin[0] && origin[1]) ? origin : (valid[0] ? [valid[0].latitude, valid[0].longitude] : [31.4187, 73.0791]);
        map = L.map('routeMap').setView(start, 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19}).addTo(map);

        const latlngs = [];
        if (origin[0] && origin[1]) {
            L.marker(origin, {title: 'Warehouse'}).addTo(map).bindPopup('<b>Warehouse start</b>').openPopup();
            latlngs.push(origin);
        }
        valid.forEach((s, i) => {
            const marker = L.marker([s.latitude, s.longitude]).addTo(map).bindPopup(`<b>${i + 1}. ${s.name}</b><br>${s.address ?? ''}<br>${s.delivery_no}`);
            marker.bindTooltip(String(i + 1), {permanent: true, direction: 'top', className: 'wms-stop-label'});
            latlngs.push([s.latitude, s.longitude]);
        });
        if (latlngs.length > 1) routeLayer = L.polyline(latlngs, {color: '#0d6efd', weight: 4}).addTo(map);
    }

    document.getElementById('useGps').addEventListener('click', () => {
        navigator.geolocation.getCurrentPosition(pos => {
            const {latitude, longitude} = pos.coords;
            if (meMarker) map.removeLayer(meMarker);
            meMarker = L.circleMarker([latitude, longitude], {radius: 8, color: '#dc3545', fillColor: '#dc3545', fillOpacity: 1}).addTo(map).bindPopup('You are here').openPopup();
            map.setView([latitude, longitude], 14);
            window.wmsLiveTracker?.push(pos.coords);
        });
    });
</script>
@endpush

@include('partials.live-tracking', ['liveDeliveryId' => $activeDeliveryId ?? null])
