<style>
    .glide-on .rider-glide { transition: transform 4.6s linear; }
    .rider-dot {
        width: 14px; height: 14px; border-radius: 50%;
        background: #dc3545; border: 2px solid #fff;
        box-shadow: 0 0 0 2px rgba(220, 53, 69, .45), 0 0 14px rgba(220, 53, 69, .85);
        position: relative;
    }
    .rider-dot::after {
        content: ''; position: absolute; inset: -6px; border-radius: 50%;
        border: 2px solid rgba(220, 53, 69, .55);
        animation: riderPulse 1.6s ease-out infinite;
    }
    .rider-dot.stale { background: #6c757d; box-shadow: 0 0 0 2px rgba(108, 117, 125, .5); }
    .rider-dot.stale::after { display: none; }
    @keyframes riderPulse {
        0% { transform: scale(.6); opacity: .9; }
        100% { transform: scale(1.7); opacity: 0; }
    }
    .leaflet-tooltip.eta-tip {
        background: rgba(13, 110, 253, .92); border: 0; color: #fff;
        font-weight: 600; font-size: 11.5px; padding: 3px 8px;
        border-radius: 6px; box-shadow: 0 2px 8px rgba(0, 0, 0, .25);
        white-space: nowrap;
    }
    .leaflet-tooltip.eta-tip::before { border-top-color: rgba(13, 110, 253, .92); }
    .leaflet-tooltip.eta-tip.stale { background: rgba(108, 117, 125, .92); }
    .leaflet-tooltip.eta-tip.stale::before { border-top-color: rgba(108, 117, 125, .92); }
</style>
