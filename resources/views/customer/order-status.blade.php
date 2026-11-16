<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    @if (in_array($order->status, ['pending_payment', 'paid', 'preparing', 'ready'], true))
        <meta http-equiv="refresh" content="15">
    @endif
    <title>Status Pesanan {{ $order->order_number }} | KOKA CAFE</title>
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
            <a href="{{ route('customer.menu') }}#menu">Menu</a>
            <a class="nav-order" href="{{ route('cart.index') }}">Keranjang</a>
        </nav>
    </header>
    <main class="order-status-page">
        <p class="eyebrow eyebrow-dark">PESANAN KOKA CAFE</p>
        <h1>Pesanan diterima<span>.</span></h1>
        <div class="order-number">{{ $order->order_number }}</div>
        <p class="order-status-label">{{ $statusLabels[$order->status] ?? 'Status sedang diperbarui' }}</p>
        <div class="order-table-label">
            @if ($order->diningTable)
                {{ $order->diningTable->label ?? 'Meja '.$order->diningTable->number }}
            @else
                Pesanan tanpa meja
            @endif
        </div>
        <section class="order-receipt" aria-label="Ringkasan pesanan">
            @foreach ($order->items as $item)
                <div class="summary-row">
                    <span>{{ $item->quantity }} × {{ $item->product_name }}</span>
                    <strong>Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</strong>
                </div>
            @endforeach
            @if ($order->promotion_code || (float) $order->discount_amount > 0)
                <div class="summary-row">
                    <span>Diskon{{ $order->promotion_code ? ' · Voucher '.$order->promotion_code : '' }}</span>
                    <strong>- Rp {{ number_format((float) $order->discount_amount, 0, ',', '.') }}</strong>
                </div>
                @if ($order->loyalty_points_redeemed > 0)
                    <p class="receipt-note">{{ $order->loyalty_points_redeemed }} poin digunakan (100 poin = Rp10.000).</p>
                @endif
            @endif
            <div class="summary-total"><span>Total</span><strong>Rp {{ number_format((float) $order->total, 0, ',', '.') }}</strong></div>
            @if ($order->loyalty_points_earned > 0)
                <p class="receipt-note">Poin yang diperoleh: {{ $order->loyalty_points_earned }}</p>
            @endif
            @if ($order->notes)
                <p class="receipt-note"><strong>Catatan:</strong> {{ $order->notes }}</p>
            @endif
        </section>
        <p class="payment-instruction">{{ $statusMessages[$order->status] ?? 'Silakan hubungi staff KOKA CAFE untuk informasi lebih lanjut.' }}</p>
        @if (session('success'))
            <p class="review-feedback" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <p class="review-feedback review-feedback-error" role="alert">{{ $errors->first() }}</p>
        @endif
        @if ($order->status === 'completed')
            @if ($order->review)
                <section class="customer-review-result" aria-label="Ulasan Anda">
                    <h2>Terima kasih atas ulasanmu.</h2>
                    <p>Penilaian: {{ $order->review->rating }} / 5</p>
                    <small>{{ $order->review->is_published ? 'Ulasan sudah ditampilkan.' : 'Ulasan menunggu moderasi admin.' }}</small>
                </section>
            @else
                <form class="customer-review-form" method="POST" action="{{ route('orders.reviews.store', $order->public_token) }}">
                    @csrf
                    <h2>Bagaimana pengalamanmu?</h2>
                    <label>Penilaian
                        <select name="rating" required>
                            <option value="">Pilih nilai</option>
                            @for ($rating = 5; $rating >= 1; $rating--)
                                <option value="{{ $rating }}" @selected((string) old('rating') === (string) $rating)>{{ $rating }} / 5</option>
                            @endfor
                        </select>
                    </label>
                    <label>Ulasan (opsional)<textarea name="comment" rows="3" maxlength="2000">{{ old('comment') }}</textarea></label>
                    <button class="button button-dark" type="submit">Kirim ulasan</button>
                </form>
            @endif
        @endif
        <a class="button button-dark" href="{{ route('customer.menu') }}#menu">Pesan menu lain <span aria-hidden="true">→</span></a>
    </main>
    <footer class="site-footer"><a class="footer-brand" href="{{ route('customer.home') }}">KOKA CAFE</a><p>Terima kasih sudah mampir.</p><span>© {{ now()->year }} KOKA CAFE</span></footer>
</body>
</html>