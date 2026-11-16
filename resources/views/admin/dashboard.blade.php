<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    <title>Dashboard Admin | KOKA CAFE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="admin-body">
    @include('admin.partials.navigation')

    <main class="admin-main">
        <section class="admin-heading">
            <div>
                <p class="admin-eyebrow">RINGKASAN HARI INI</p>
                <h1>Dashboard admin.</h1>
                <p class="admin-subtitle">Pantau penjualan dan aktivitas operasional KOKA CAFE.</p>
            </div>
            <a class="admin-primary-button" href="{{ route('admin.reports.sales') }}">Lihat laporan penjualan</a>
        </section>

        <section class="dashboard-grid" aria-label="Ringkasan penjualan hari ini">
            <article class="dashboard-card">
                <span>Penjualan hari ini</span>
                <strong>Rp {{ number_format((float) $todaySales, 0, ',', '.') }}</strong>
                <small>Dari transaksi yang sudah dibayar</small>
            </article>
            <article class="dashboard-card">
                <span>Pesanan dibayar</span>
                <strong>{{ number_format($todayOrderCount, 0, ',', '.') }}</strong>
                <small>Transaksi hari ini</small>
            </article>
            <article class="dashboard-card">
                <span>Rata-rata pesanan</span>
                <strong>Rp {{ number_format((float) $averageOrderValue, 0, ',', '.') }}</strong>
                <small>Nilai rata-rata transaksi hari ini</small>
            </article>
            <article class="dashboard-card">
                <span>Pesanan aktif</span>
                <strong>{{ number_format($activeOrders, 0, ',', '.') }}</strong>
                <small>Menunggu bayar hingga siap disajikan</small>
            </article>
        </section>

        <section class="dashboard-actions" aria-label="Akses cepat">
            <a href="{{ route('staff.orders.index') }}">
                <span>01 / PESANAN</span>
                <strong>Konfirmasi pembayaran dan kelola pesanan</strong>
                <small>Lihat semua pesanan masuk dan perbarui statusnya.</small>
            </a>
            <a href="{{ route('staff.orders.index', ['queue' => 1]) }}">
                <span>02 / DAPUR</span>
                <strong>Buka antrean pesanan</strong>
                <small>Fokus pada pesanan yang telah dibayar dan sedang disiapkan.</small>
            </a>
            <a href="{{ route('admin.tables.index') }}">
                <span>03 / MEJA</span>
                <strong>Kelola meja dan QR</strong>
                <small>Atur meja aktif dan cetak QR untuk pelanggan.</small>
            </a>
            <a href="{{ route('admin.reports.sales') }}">
                <span>04 / LAPORAN</span>
                <strong>Laporan penjualan</strong>
                <small>Tinjau penjualan, metode pembayaran, dan produk terlaris.</small>
            </a>
            <a href="{{ route('admin.products.index') }}">
                <span>05 / KATALOG</span>
                <strong>Kelola menu dan produk</strong>
                <small>Ubah harga, foto, kategori, ketersediaan, dan produk favorit.</small>
            </a>
        </section>
    </main>
</body>
</html>
