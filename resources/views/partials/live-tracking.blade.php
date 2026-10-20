@if (auth()->user()?->isRider())
@push('scripts')
<script>
(function () {
    const endpoint = @json(route('deliveries.location'));
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const MIN_INTERVAL_MS = 5000;

    let deliveryId = @json($liveDeliveryId ?? null);
    let lastSentAt = 0;
    let lastFix = null;

    const haversineKm = (a, b) => {
        const R = 6371;
        const toRad = d => d * Math.PI / 180;
        const dLat = toRad(b.latitude - a.latitude);
        const dLng = toRad(b.longitude - a.longitude);
        const s = Math.sin(dLat / 2) ** 2
            + Math.cos(toRad(a.latitude)) * Math.cos(toRad(b.latitude)) * Math.sin(dLng / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(s), Math.sqrt(1 - s));
    };

    const speedFrom = coords => {
        if (typeof coords.speed === 'number' && isFinite(coords.speed) && coords.speed >= 0) {
            return Math.round(coords.speed * 3.6 * 10) / 10;
        }
        if (lastFix && coords.timestamp && lastFix.timestamp) {
            const hours = (coords.timestamp - lastFix.timestamp) / 3600000;
            if (hours > 0) {
                const kmh = haversineKm(lastFix, coords) / hours;
                if (isFinite(kmh) && kmh >= 0) return Math.round(kmh * 10) / 10;
            }
        }
        return null;
    };

    const send = (coords, force = false) => {
        const now = Date.now();
        if (!force && now - lastSentAt < MIN_INTERVAL_MS) return;
        lastSentAt = now;

        const speed = speedFrom(coords);
        lastFix = {
            latitude: coords.latitude,
            longitude: coords.longitude,
            timestamp: coords.timestamp || now,
        };

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                latitude: coords.latitude,
                longitude: coords.longitude,
                speed: speed,
                delivery_id: deliveryId,
                label: 'live',
            }),
        }).catch(() => { /* offline / transient — the next fix retries */ });
    };

    window.wmsLiveTracker = {
        push(coords, force = true) { send(coords, force); },
        setDelivery(id) { deliveryId = id; },
    };

    if ('geolocation' in navigator) {
        const options = {enableHighAccuracy: true, maximumAge: 5000, timeout: 15000};
        navigator.geolocation.watchPosition(pos => send(pos.coords), () => {}, options);
        navigator.geolocation.getCurrentPosition(pos => send(pos.coords, true), () => {}, options);
    }
})();
</script>
@endpush
@endif
