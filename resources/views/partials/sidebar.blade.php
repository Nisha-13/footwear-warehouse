@php
    $u = auth()->user();
    $a = fn (...$patterns) => request()->routeIs(...$patterns) ? 'active' : '';
@endphp

<ul class="wms-nav">
    <li>
        <a href="{{ route('dashboard') }}" class="{{ $a('dashboard') }}">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
    </li>

    @if ($u->isSuperAdmin())
        <li class="nav-section">Master Data</li>
        <li><a href="{{ route('warehouses.index') }}" class="{{ $a('warehouses.*') }}"><i class="bi bi-buildings"></i> Warehouses</a></li>
        <li><a href="{{ route('shops.index') }}" class="{{ $a('shops.*') }}"><i class="bi bi-shop"></i> Retail Shops</a></li>
        <li><a href="{{ route('users.index') }}" class="{{ $a('users.*') }}"><i class="bi bi-people"></i> Users &amp; Roles</a></li>
        <li><a href="{{ route('suppliers.index') }}" class="{{ $a('suppliers.*') }}"><i class="bi bi-truck"></i> Suppliers</a></li>
        <li><a href="{{ route('brands.index') }}" class="{{ $a('brands.*') }}"><i class="bi bi-award"></i> Brands</a></li>
        <li><a href="{{ route('styles.index') }}" class="{{ $a('styles.*') }}"><i class="bi bi-palette"></i> Styles</a></li>
        <li><a href="{{ route('categories.index') }}" class="{{ $a('categories.*') }}"><i class="bi bi-diagram-3"></i> Categories</a></li>
        <li><a href="{{ route('age-groups.index') }}" class="{{ $a('age-groups.*') }}"><i class="bi bi-rulers"></i> Age Groups</a></li>

        <li class="nav-section">Catalog</li>
        <li><a href="{{ route('products.index') }}" class="{{ $a('products.*') }}"><i class="bi bi-shoe-prints"></i> Products</a></li>
        <li><a href="{{ route('inventory.index') }}" class="{{ $a('inventory.*') }}"><i class="bi bi-boxes"></i> Inventory</a></li>

        <li class="nav-section">Procurement</li>
        <li><a href="{{ route('purchases.index') }}" class="{{ $a('purchases.*') }}"><i class="bi bi-cart-check"></i> Purchases</a></li>
        <li><a href="{{ route('ledger.index') }}" class="{{ $a('ledger.*') }}"><i class="bi bi-journal-text"></i> Supplier Ledger</a></li>

        <li class="nav-section">Distribution &amp; Logistics</li>
        <li><a href="{{ route('transfers.index') }}" class="{{ $a('transfers.*') }}"><i class="bi bi-arrow-left-right"></i> Stock Transfers</a></li>
        <li><a href="{{ route('deliveries.index') }}" class="{{ $a('deliveries.*') }}"><i class="bi bi-truck-front"></i> Deliveries</a></li>
        <li><a href="{{ route('tracking.index') }}" class="{{ $a('tracking.*') }}"><i class="bi bi-geo-alt"></i> Rider Tracking</a></li>

        <li class="nav-section">Insights</li>
        <li><a href="{{ route('reports.index') }}" class="{{ $a('reports.*') }}"><i class="bi bi-graph-up-arrow"></i> Reports &amp; Analytics</a></li>
    @endif

    @if ($u->isAccountant())
        <li class="nav-section">Finance</li>
        <li><a href="{{ route('purchases.index') }}" class="{{ $a('purchases.*') }}"><i class="bi bi-cart-check"></i> Purchases</a></li>
        <li><a href="{{ route('ledger.index') }}" class="{{ $a('ledger.*') }}"><i class="bi bi-journal-text"></i> Supplier Ledger</a></li>

        <li class="nav-section">Insights</li>
        <li><a href="{{ route('reports.index') }}" class="{{ $a('reports.*') }}"><i class="bi bi-graph-up-arrow"></i> Reports &amp; Analytics</a></li>
    @endif

    @if ($u->isWarehouseManager())
        <li class="nav-section">Warehouse Operations</li>
        <li><a href="{{ route('inventory.index') }}" class="{{ $a('inventory.*') }}"><i class="bi bi-boxes"></i> Inventory</a></li>
        <li><a href="{{ route('transfers.index') }}" class="{{ $a('transfers.*') }}"><i class="bi bi-arrow-left-right"></i> Transfers</a></li>
        <li><a href="{{ route('deliveries.index') }}" class="{{ $a('deliveries.*') }}"><i class="bi bi-truck-front"></i> Deliveries</a></li>
        <li><a href="{{ route('tracking.index') }}" class="{{ $a('tracking.*') }}"><i class="bi bi-geo-alt"></i> Rider Tracking</a></li>
        <li><a href="{{ route('shops.index') }}" class="{{ $a('shops.*') }}"><i class="bi bi-shop"></i> My Shops</a></li>
        <li><a href="{{ route('reports.index') }}" class="{{ $a('reports.*') }}"><i class="bi bi-graph-up-arrow"></i> Reports</a></li>
    @endif

    @if ($u->isShopkeeper())
        <li class="nav-section">Shop Operations</li>
        <li><a href="{{ route('shop.inventory') }}" class="{{ $a('shop.inventory') }}"><i class="bi bi-boxes"></i> My Inventory</a></li>
        <li><a href="{{ route('shop.sales.create') }}" class="{{ $a('shop.sales.create') }}"><i class="bi bi-basket"></i> New Sale</a></li>
        <li><a href="{{ route('shop.sales.index') }}" class="{{ $a('shop.sales.index') }}"><i class="bi bi-receipt"></i> Sales History</a></li>
        <li><a href="{{ route('transfers.index') }}" class="{{ $a('transfers.*') }}"><i class="bi bi-inbox"></i> Incoming Transfers</a></li>
    @endif

    @if ($u->isRider())
        <li class="nav-section">Field Logistics</li>
        <li><a href="{{ route('rider.dashboard') }}" class="{{ $a('rider.dashboard') }}"><i class="bi bi-speedometer2"></i> My Dashboard</a></li>
        <li><a href="{{ route('rider.route') }}" class="{{ $a('rider.route') }}"><i class="bi bi-signpost-split"></i> Optimised Route</a></li>
        <li><a href="{{ route('rider.deliveries.index') }}" class="{{ $a('rider.deliveries.*') }}"><i class="bi bi-list-check"></i> My Deliveries</a></li>
    @endif

    <li class="nav-section">Account</li>
    <li><a href="{{ route('profile.show') }}" class="{{ $a('profile.*') }}"><i class="bi bi-person-gear"></i> My Profile</a></li>
</ul>
