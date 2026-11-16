@php
    $currentAdminSection = match (true) {
        request()->routeIs('admin.dashboard') => 'dashboard',
        request()->routeIs('staff.orders.index') && request()->boolean('queue') => 'queue',
        request()->routeIs('staff.orders.index') => 'orders',
        request()->routeIs('admin.products.*', 'admin.categories.*') => 'catalog',
        request()->routeIs('admin.inventory.*') => 'inventory',
        request()->routeIs('admin.tables.*') => 'tables',
        request()->routeIs('admin.reservations.*') => 'reservations',
        request()->routeIs('admin.customers.*') => 'customers',
        request()->routeIs('admin.reviews.*') => 'reviews',
        request()->routeIs('admin.promotions.*') => 'promotions',
        request()->routeIs('admin.reports.*') => 'reports',
        default => null,
    };
@endphp

<header class="admin-topbar">
    <a class="admin-brand" href="{{ route('admin.dashboard') }}">
        <img class="admin-brand-image" src="{{ asset('images/branding/koka-logo.png') }}" alt="">
        <span><strong>KOKA</strong><small>Kopiku &amp; kamu</small></span>
    </a>
    <nav class="admin-nav" aria-label="Navigasi admin">
        <a class="{{ $currentAdminSection === 'dashboard' ? 'is-current' : '' }}" href="{{ route('admin.dashboard') }}" @if ($currentAdminSection === 'dashboard') aria-current="page" @endif>Dashboard</a>
        <a class="{{ $currentAdminSection === 'orders' ? 'is-current' : '' }}" href="{{ route('staff.orders.index') }}" @if ($currentAdminSection === 'orders') aria-current="page" @endif>Pesanan</a>
        <a class="{{ $currentAdminSection === 'queue' ? 'is-current' : '' }}" href="{{ route('staff.orders.index', ['queue' => 1]) }}" @if ($currentAdminSection === 'queue') aria-current="page" @endif>Antrean dapur</a>
        <a class="{{ $currentAdminSection === 'catalog' ? 'is-current' : '' }}" href="{{ route('admin.products.index') }}" @if ($currentAdminSection === 'catalog') aria-current="page" @endif>Menu &amp; Produk</a>
        <a class="{{ $currentAdminSection === 'inventory' ? 'is-current' : '' }}" href="{{ route('admin.inventory.index') }}" @if ($currentAdminSection === 'inventory') aria-current="page" @endif>Inventaris</a>
        <a class="{{ $currentAdminSection === 'tables' ? 'is-current' : '' }}" href="{{ route('admin.tables.index') }}" @if ($currentAdminSection === 'tables') aria-current="page" @endif>Meja &amp; QR</a>
        <a class="{{ $currentAdminSection === 'reservations' ? 'is-current' : '' }}" href="{{ route('admin.reservations.index') }}" @if ($currentAdminSection === 'reservations') aria-current="page" @endif>Reservasi</a>
        <a class="{{ $currentAdminSection === 'customers' ? 'is-current' : '' }}" href="{{ route('admin.customers.index') }}" @if ($currentAdminSection === 'customers') aria-current="page" @endif>Pelanggan</a>
        <a class="{{ $currentAdminSection === 'reviews' ? 'is-current' : '' }}" href="{{ route('admin.reviews.index') }}" @if ($currentAdminSection === 'reviews') aria-current="page" @endif>Ulasan</a>
        <a class="{{ $currentAdminSection === 'promotions' ? 'is-current' : '' }}" href="{{ route('admin.promotions.index') }}" @if ($currentAdminSection === 'promotions') aria-current="page" @endif>Voucher</a>
        <a class="{{ $currentAdminSection === 'reports' ? 'is-current' : '' }}" href="{{ route('admin.reports.sales') }}" @if ($currentAdminSection === 'reports') aria-current="page" @endif>Laporan</a>
        <a href="{{ route('customer.home') }}" target="_blank" rel="noopener noreferrer">Menu customer</a>
    </nav>
    <form method="POST" action="{{ route('staff.logout') }}">
        @csrf
        <button class="admin-logout" type="submit">Keluar</button>
    </form>
</header>
