<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    <title>{{ $customer->name }} | Pelanggan KOKA CAFE</title>
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
                <h1>{{ $customer->name }}.</h1>
                <p class="admin-subtitle">{{ $customer->phone }}{{ $customer->email ? ' · '.$customer->email : '' }}</p>
                <p class="admin-subtitle"><strong>{{ number_format($customer->loyalty_points, 0, ',', '.') }} poin loyalitas</strong> · 100 poin = Rp10.000</p>
            </div>
            <a class="admin-primary-button" href="{{ route('admin.customers.index') }}">Kembali ke pelanggan</a>
        </section>

        @if (session('success'))
            <p class="admin-alert admin-alert-success" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <p class="admin-alert" role="alert">{{ $errors->first() }}</p>
        @endif

        <details class="catalog-create-panel">
            <summary>Edit profil pelanggan</summary>
            <form class="catalog-form" method="POST" action="{{ route('admin.customers.update', $customer) }}">
                @csrf
                @method('PUT')
                <label>Nama<input name="name" value="{{ $customer->name }}" maxlength="100" required></label>
                <label>Nomor HP<input name="phone" type="tel" value="{{ $customer->phone }}" maxlength="24" required></label>
                <label>Email<input name="email" type="email" value="{{ $customer->email }}" maxlength="255"></label>
                <button class="admin-primary-button" type="submit">Simpan profil</button>
            </form>
        </details>

        <section class="inventory-section" aria-labelledby="customer-history-heading">
            <div class="inventory-section-heading">
                <div>
                    <p class="admin-eyebrow">RIWAYAT TRANSAKSI</p>
                    <h2 id="customer-history-heading">Pesanan pelanggan.</h2>
                </div>
            </div>
            <div class="customer-order-list">
                @forelse ($customer->orders as $order)
                    <article class="reservation-card">
                        <div class="reservation-card-heading">
                            <div>
                                <p class="admin-eyebrow">{{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->order_number }}</p>
                                <h2>Rp {{ number_format((float) $order->total, 0, ',', '.') }}</h2>
                                <p>{{ $order->diningTable?->label ?: ($order->diningTable ? 'Meja '.$order->diningTable->number : 'Pesanan tanpa meja') }}</p>
                                @if ((float) $order->discount_amount > 0)
                                    <p>Diskon Rp {{ number_format((float) $order->discount_amount, 0, ',', '.') }}{{ $order->promotion_code ? ' · '.$order->promotion_code : '' }}</p>
                                @endif
                                @if ($order->loyalty_points_earned > 0)
                                    <p>{{ $order->loyalty_points_earned }} poin diperoleh</p>
                                @endif
                            </div>
                            <span class="reservation-status reservation-status-{{ $order->status === 'cancelled' ? 'cancelled' : ($order->status === 'completed' ? 'completed' : 'confirmed') }}">{{ $order->status }}</span>
                        </div>
                        <ul class="customer-order-items">
                            @foreach ($order->items as $item)
                                <li>{{ $item->quantity }} × {{ $item->product_name }} <span>Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</span></li>
                            @endforeach
                        </ul>
                    </article>
                @empty
                    <div class="orders-empty"><h2>Belum ada riwayat pesanan.</h2></div>
                @endforelse
            </div>
        </section>
    </main>
</body>
</html>
