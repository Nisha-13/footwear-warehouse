@props(['label', 'value', 'icon' => 'bi-graph-up', 'tone' => 'primary', 'hint' => null])

<div class="stat-card p-3 h-100">
    <div class="d-flex align-items-center gap-3">
        <div class="stat-icon" style="background: rgba(var(--bs-{{ $tone }}-rgb), .12); color: var(--bs-{{ $tone }});">
            <i class="bi {{ $icon }}"></i>
        </div>
        <div class="min-w-0">
            <div class="text-muted small">{{ $label }}</div>
            <div class="fw-bold fs-5 lh-1 mt-1">{{ $value }}</div>
            @if ($hint)
                <div class="small text-muted mt-1">{{ $hint }}</div>
            @endif
        </div>
    </div>
</div>
