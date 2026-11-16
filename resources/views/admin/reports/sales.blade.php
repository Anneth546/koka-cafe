<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    <title>Laporan Penjualan | KOKA CAFE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="admin-body">
    @include('admin.partials.navigation')

    <main class="admin-main">
        <section class="admin-heading report-heading">
            <div>
                <p class="admin-eyebrow">ANALITIK / PENJUALAN</p>
                <h1>Laporan penjualan.</h1>
                <p class="admin-subtitle">Ringkasan dihitung dari transaksi yang sudah dibayar, tidak termasuk pesanan batal.</p>
            </div>
            <form class="report-filter-form" method="GET" action="{{ route('admin.reports.sales') }}">
                <label for="report-from">Dari tanggal</label>
                <input id="report-from" name="from" type="date" value="{{ $fromDate }}" required>
                <label for="report-to">Sampai tanggal</label>
                <input id="report-to" name="to" type="date" value="{{ $toDate }}" required>
                <button class="admin-primary-button" type="submit">Terapkan</button>
            </form>
        </section>

        @if ($errors->any())
            <p class="admin-alert" role="alert">{{ $errors->first() }}</p>
        @endif

        <section class="dashboard-grid report-summary" aria-label="Ringkasan laporan">
            <article class="dashboard-card">
                <span>Total penjualan</span>
                <strong>Rp {{ number_format((float) $salesTotal, 0, ',', '.') }}</strong>
                <small>{{ $fromDate }} sampai {{ $toDate }}</small>
            </article>
            <article class="dashboard-card">
                <span>Jumlah transaksi</span>
                <strong>{{ number_format($orderCount, 0, ',', '.') }}</strong>
                <small>Transaksi dibayar dalam periode</small>
            </article>
            <article class="dashboard-card">
                <span>Total diskon</span>
                <strong>Rp {{ number_format((float) $discountTotal, 0, ',', '.') }}</strong>
                <small>Voucher dan penukaran poin</small>
            </article>
            <article class="dashboard-card">
                <span>Rata-rata transaksi</span>
                <strong>Rp {{ number_format((float) $averageOrderValue, 0, ',', '.') }}</strong>
                <small>Penjualan dibagi jumlah transaksi</small>
            </article>
        </section>

        <section class="report-panels">
            <article class="report-panel">
                <h2>Penjualan per hari</h2>
                @if ($dailySales->isEmpty())
                    <p class="report-empty">Belum ada transaksi dibayar pada periode ini.</p>
                @else
                    <div class="report-table-wrap">
                        <table class="report-table">
                            <thead>
                                <tr><th>Tanggal</th><th>Transaksi</th><th>Diskon</th><th>Penjualan bersih</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($dailySales as $day)
                                    <tr>
                                        <td>{{ $day->sales_date }}</td>
                                        <td>{{ number_format($day->order_count, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format((float) $day->discounts, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format((float) $day->revenue, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </article>

            <article class="report-panel">
                <h2>Penjualan per metode pembayaran</h2>
                @if ($paymentBreakdown->isEmpty())
                    <p class="report-empty">Belum ada data pembayaran.</p>
                @else
                    <div class="report-table-wrap">
                        <table class="report-table">
                            <thead>
                                <tr><th>Metode</th><th>Transaksi</th><th>Diskon</th><th>Penjualan bersih</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($paymentBreakdown as $payment)
                                    <tr>
                                        <td>{{ $paymentMethods[$payment->payment_method] ?? 'Tidak tercatat' }}</td>
                                        <td>{{ number_format($payment->order_count, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format((float) $payment->discounts, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format((float) $payment->revenue, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </article>

            <article class="report-panel report-panel-wide">
                <h2>Produk terlaris</h2>
                @if ($bestSellers->isEmpty())
                    <p class="report-empty">Belum ada produk terjual pada periode ini.</p>
                @else
                    <div class="report-table-wrap">
                        <table class="report-table">
                            <thead>
                                <tr><th>Produk</th><th>Jumlah terjual</th><th>Penjualan</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($bestSellers as $product)
                                    <tr>
                                        <td>{{ $product->product_name }}</td>
                                        <td>{{ number_format($product->quantity, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format((float) $product->revenue, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </article>
        </section>
    </main>
</body>
</html>
