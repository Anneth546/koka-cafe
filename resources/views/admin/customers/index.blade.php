<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    <title>Pelanggan | KOKA CAFE</title>
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
                <p class="admin-eyebrow">PELANGGAN / RIWAYAT</p>
                <h1>Data pelanggan.</h1>
                <p class="admin-subtitle">Profil dibuat dari nomor HP yang dicantumkan pada pesanan customer atau walk-in.</p>
            </div>
            <form class="customer-search-form" method="GET" action="{{ route('admin.customers.index') }}">
                <label for="customer-search">Cari nama atau nomor HP</label>
                <input id="customer-search" name="q" value="{{ $searchTerm }}" maxlength="80">
                <button class="admin-primary-button" type="submit">Cari</button>
            </form>
        </section>

        @if (session('success'))
            <p class="admin-alert admin-alert-success" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <p class="admin-alert" role="alert">{{ $errors->first() }}</p>
        @endif

        <section class="report-table-wrap" aria-label="Daftar pelanggan">
            <table class="report-table">
                <thead>
                    <tr><th>Nama</th><th>Nomor HP</th><th>Poin</th><th>Total pesanan</th><th>Penjualan dibayar</th><th>Pesanan terakhir</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td><strong>{{ $customer->name }}</strong></td>
                            <td>{{ $customer->phone }}</td>
                            <td>{{ number_format($customer->loyalty_points, 0, ',', '.') }}</td>
                            <td>{{ number_format($customer->orders_count, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) ($customer->paid_orders_sum_total ?? 0), 0, ',', '.') }}</td>
                            <td>{{ $customer->last_order_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td><a class="admin-text-link" href="{{ route('admin.customers.show', $customer) }}">Riwayat</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Belum ada pelanggan dengan nomor HP tersimpan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        @if ($customers->hasPages())
            <nav class="order-pagination" aria-label="Navigasi halaman pelanggan">
                {{ $customers->links() }}
            </nav>
        @endif
    </main>
</body>
</html>
