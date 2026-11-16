<header class="site-header">
    <a class="brand" href="{{ route('customer.home') }}" aria-label="KOKA, Kopiku dan kamu, beranda">
        @if (file_exists(public_path('images/branding/koka-logo.png')))
            <img class="brand-image" src="{{ asset('images/branding/koka-logo.png') }}" alt="">
        @else
            <span class="brand-fallback" aria-hidden="true">K</span>
        @endif
        <span class="brand-copy">
            <span class="brand-name">KOKA</span>
            <span class="brand-tagline">Kopiku &amp; kamu</span>
        </span>
    </a>
    <nav class="main-nav" aria-label="Navigasi utama">
        @php($cartCount = array_sum(session('cart', [])))
        <a href="{{ route('customer.home') }}">Beranda</a>
        <a href="{{ route('customer.menu') }}#menu">Menu</a>
        <a href="{{ route('customer.menu') }}#pilihan">Favorit</a>
        <a href="{{ route('customer.home') }}#cerita">Tentang KOKA</a>
        <a class="{{ request()->routeIs('customer.reservations.*') ? 'is-current' : '' }}" href="{{ route('customer.reservations.create') }}">Reservasi meja</a>
        <a class="nav-order" href="{{ route('cart.index') }}" aria-label="Keranjang, {{ $cartCount }} item">
            Keranjang <span class="cart-count">{{ $cartCount }}</span>
        </a>
        <a class="staff-login-link" href="{{ route('staff.login') }}">Login staff</a>
    </nav>
</header>
