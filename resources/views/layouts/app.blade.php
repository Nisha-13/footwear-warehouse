<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} &middot; Footwear WMS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { corePlugins: { preflight: false } };</script>

    @stack('styles')
</head>
<body class="wms-body">
<div class="wms-shell">
    <aside class="wms-sidebar" id="wmsSidebar">
        <a href="{{ route('dashboard') }}" class="brand">
            <span class="logo"><i class="bi bi-box-seam-fill"></i></span>
            <span>Footwear WMS</span>
        </a>
        @include('partials.sidebar')
    </aside>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="wms-main">
        <header class="wms-topbar">
            <button class="btn btn-light d-lg-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <div class="flex-grow-1">
                <h5 class="mb-0 fw-semibold">{{ $title ?? 'Dashboard' }}</h5>
                @isset($subtitle)
                    <small class="text-muted">{{ $subtitle }}</small>
                @endisset
            </div>
            <div class="text-end d-none d-md-block me-3">
                <div class="small text-muted">{{ now()->format('D, d M Y') }}</div>
            </div>
            <div class="dropdown">
                <button class="btn btn-light d-flex align-items-center gap-2 dropdown-toggle" data-bs-toggle="dropdown">
                    <span class="wms-avatar">{{ auth()->user()->initials }}</span>
                    <span class="text-start d-none d-sm-block">
                        <span class="d-block small fw-semibold lh-1">{{ auth()->user()->name }}</span>
                        <span class="d-block text-muted" style="font-size:.7rem">{{ auth()->user()->role_label }}</span>
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bi bi-person-gear me-2"></i>My Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <main class="wms-content">
            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@include('partials.flash-modal')
@include('partials.password-toggle')
<script>
    const sidebar = document.getElementById('wmsSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const toggle = document.getElementById('sidebarToggle');
    if (toggle) {
        toggle.addEventListener('click', () => { sidebar.classList.add('open'); backdrop.style.display = 'block'; });
        backdrop.addEventListener('click', () => { sidebar.classList.remove('open'); backdrop.style.display = 'none'; });
    }
</script>
@stack('scripts')
</body>
</html>
