<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    <title>Keranjang | KOKA CAFE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}">
</head>
<body>
    <header class="site-header cart-header">
        <a class="brand" href="{{ route('customer.home') }}"><span class="brand-name">KOKA</span><span class="brand-placeholder">CAFE SMART SHOP</span></a>
        <nav class="main-nav" aria-label="Navigasi utama">
            <a href="{{ route('customer.home') }}">Beranda</a>
            <a href="{{ route('customer.menu') }}#menu">Tambah menu</a>
            <a class="nav-order" href="{{ route('cart.index') }}">Keranjang <span class="cart-count">{{ $cartCount }}</span></a>
        </nav>
    </header>

    <main class="cart-page">
        <div class="cart-heading">
            <div>
                <p class="eyebrow eyebrow-dark">PESANAN KOKA CAFE</p>
                <h1>Keranjang <span>belanja.</span></h1>
            </div>
            <a class="back-to-menu" href="{{ route('customer.menu') }}#menu">← Kembali ke menu</a>
        </div>

        @if (session('success'))
            <p class="notice notice-success" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <div class="notice notice-error" role="alert">{{ $errors->first() }}</div>
        @endif

        @if ($products->isEmpty())
            <section class="cart-empty">
                <span aria-hidden="true">✳</span>
                <h2>Belum ada yang dipilih.</h2>
                <p>Pilih kopi, makanan, atau camilan untuk memulai.</p>
                <a class="button button-dark" href="{{ route('customer.menu') }}#menu">Jelajahi menu <span aria-hidden="true">→</span></a>
            </section>
        @else
            <div class="cart-layout">
                <section class="cart-lines" aria-label="Produk dalam keranjang">
                    @foreach ($products as $product)
                        <article class="cart-line">
                            <div class="cart-thumb">
                                @if ($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                                @else
                                    <span aria-hidden="true">K</span>
                                @endif
                            </div>
                            <div class="cart-product-info">
                                <span class="cart-category">{{ $product->category->name }}</span>
                                <h2>{{ $product->name }}</h2>
                                <p>Rp {{ number_format((float) $product->price, 0, ',', '.') }}</p>
                            </div>
                            <form class="quantity-form" method="POST" action="{{ route('cart.items.update', $product) }}">
                                @csrf
                                @method('PATCH')
                                <label for="quantity-{{ $product->id }}">Jumlah</label>
                                <input id="quantity-{{ $product->id }}" type="number" name="quantity" min="1" max="99" value="{{ $cart[$product->id] }}">
                                <button type="submit">Perbarui</button>
                            </form>
                            <form method="POST" action="{{ route('cart.items.destroy', $product) }}">
                                @csrf
                                @method('DELETE')
                                <button class="remove-item" type="submit" aria-label="Hapus {{ $product->name }}">Hapus</button>
                            </form>
                            <strong class="line-total">Rp {{ number_format((float) $product->price * $cart[$product->id], 0, ',', '.') }}</strong>
                        </article>
                    @endforeach
                </section>

                <aside class="cart-summary">
                    <p class="eyebrow eyebrow-dark">RINGKASAN</p>
                    <div class="summary-row"><span>{{ $cartCount }} item</span><strong>Rp {{ number_format($subtotal, 0, ',', '.') }}</strong></div>
                    <div class="summary-total"><span>Total sementara</span><strong>Rp {{ number_format($subtotal, 0, ',', '.') }}</strong></div>
                    <p class="checkout-note">Pembayaran dilakukan di kasir setelah pesanan dikirim.</p>
                    @if ($table)
                        <p class="table-note">Pesanan akan dikirim untuk <strong>{{ $table->label ?? 'Meja '.$table->number }}</strong>.</p>
                    @else
                        <p class="table-note">Anda bisa langsung memesan tanpa QR meja. Scan QR jika ingin menghubungkan pesanan ke meja.</p>
                    @endif
                    <form class="checkout-form" method="POST" action="{{ route('orders.store') }}">
                        @csrf
                        <input type="hidden" name="checkout_key" value="{{ $checkoutKey }}">
                        <label for="customer-name">Nama (opsional)</label>
                        <input id="customer-name" name="customer_name" type="text" maxlength="100" value="{{ old('customer_name') }}" placeholder="Nama pemesan">
                        <label for="customer-phone">Nomor HP (opsional)</label>
                        <input id="customer-phone" name="customer_phone" type="tel" maxlength="24" value="{{ old('customer_phone') }}" placeholder="08xxxxxxxxxx" autocomplete="tel">
                        <label for="promotion-code">Kode voucher (opsional)</label>
                        <input id="promotion-code" name="promotion_code" type="text" maxlength="40" value="{{ old('promotion_code') }}" placeholder="Contoh: HEMAT10" autocomplete="off">
                        <p class="checkout-note">Satu voucher per pesanan. Voucher dan poin dapat digunakan bersamaan.</p>
                        <label>
                            <input type="hidden" name="redeem_points" value="0">
                            <input type="checkbox" name="redeem_points" value="1" @checked(old('redeem_points'))>
                            Gunakan poin (100 poin = Rp10.000)
                        </label>
                        <label for="order-notes">Catatan untuk barista</label>
                        <textarea id="order-notes" name="notes" maxlength="1000" rows="3" placeholder="Contoh: less sugar">{{ old('notes') }}</textarea>
                        <button class="button button-dark" type="submit">Kirim pesanan <span aria-hidden="true">→</span></button>
                    </form>
                </aside>
            </div>
        @endif
    </main>

    <footer class="site-footer"><a class="footer-brand" href="{{ route('customer.home') }}">KOKA CAFE</a><p>Pesan menu favoritmu lewat keranjang.</p><span>© {{ now()->year }} KOKA CAFE</span></footer>
</body>
</html>