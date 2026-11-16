<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    <meta http-equiv="refresh" content="15">
    <title>Pesanan | KOKA CAFE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="admin-body">
    @include('admin.partials.navigation')

    <main class="admin-main">
        <section class="admin-heading order-heading">
            <div>
                <p class="admin-eyebrow">{{ $isQueue ? 'OPERASIONAL / DAPUR' : 'OPERASIONAL / PESANAN' }}</p>
                <h1>{{ $isQueue ? 'Antrean dapur.' : 'Pesanan masuk.' }}</h1>
                <p class="admin-subtitle">{{ $isQueue ? 'Pesanan yang sudah dibayar hingga siap disajikan.' : 'Kelola pembayaran, proses pesanan, dan penyelesaian.' }} Daftar diperbarui otomatis setiap 15 detik.</p>
            </div>
        </section>

        @if (session('success'))
            <p class="admin-alert admin-alert-success" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <p class="admin-alert" role="alert">{{ $errors->first() }}</p>
        @endif

        @unless ($isQueue)
            <details class="manual-order-panel">
                <summary>Buat pesanan manual / walk-in</summary>
                @if ($availableProducts->isEmpty())
                    <p class="report-empty">Tidak ada produk tersedia untuk dipesan.</p>
                @else
                    <form method="POST" action="{{ route('staff.orders.manual.store') }}">
                        @csrf
                        <div class="manual-order-fields">
                            <label>Nama pelanggan (opsional)<input name="customer_name" maxlength="100" value="{{ old('customer_name') }}"></label>
                            <label>Nomor HP (opsional)<input name="customer_phone" type="tel" maxlength="24" value="{{ old('customer_phone') }}"></label>
                            <label>Meja
                                <select name="dining_table_id">
                                    <option value="">Tanpa meja / take away</option>
                                    @foreach ($tables->where('is_active', true) as $table)
                                        <option value="{{ $table->id }}" @selected((string) old('dining_table_id') === (string) $table->id)>{{ $table->label ?: 'Meja '.$table->number }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="manual-notes">Catatan<textarea name="notes" rows="2" maxlength="1000">{{ old('notes') }}</textarea></label>
                        </div>
                        <div class="manual-order-fields">
                            <label>Kode voucher (opsional)<input name="promotion_code" maxlength="40" value="{{ old('promotion_code') }}" autocomplete="off"></label>
                            <label class="manual-notes">
                                <span>Penukaran poin</span>
                                <span><input type="hidden" name="redeem_points" value="0"><input type="checkbox" name="redeem_points" value="1" @checked(old('redeem_points'))> Gunakan 100 poin = Rp10.000</span>
                            </label>
                        </div>
                        <p class="admin-eyebrow manual-order-label">PRODUK DAN JUMLAH</p>
                        <div class="manual-product-grid">
                            @foreach ($availableProducts as $product)
                                <label>
                                    <span>{{ $product->name }} <small>Rp {{ number_format((float) $product->price, 0, ',', '.') }}</small></span>
                                    <input name="items[{{ $product->id }}]" type="number" min="0" max="99" value="{{ old('items.'.$product->id, 0) }}" aria-label="Jumlah {{ $product->name }}">
                                </label>
                            @endforeach
                        </div>
                        <button class="admin-primary-button" type="submit">Buat pesanan</button>
                    </form>
                @endif
            </details>
        @endunless

        <form class="order-filter-form" method="GET" action="{{ route('staff.orders.index') }}">
            @if ($isQueue)
                <input type="hidden" name="queue" value="1">
            @endif
            <div class="order-filter-fields">
                <label for="order-number-filter">Nomor pesanan<input id="order-number-filter" name="order_number" value="{{ request('order_number') }}" maxlength="24"></label>
                <label for="order-table-filter">Meja
                    <select id="order-table-filter" name="table_id">
                        <option value="">Semua meja</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table->id }}" @selected((string) request('table_id') === (string) $table->id)>{{ $table->label ?: 'Meja '.$table->number }}</option>
                        @endforeach
                    </select>
                </label>
                <label for="order-from-filter">Dari tanggal<input id="order-from-filter" name="from" type="date" value="{{ request('from') }}"></label>
                <label for="order-to-filter">Sampai tanggal<input id="order-to-filter" name="to" type="date" value="{{ request('to') }}"></label>
                <label for="order-status-filter">Status
                    <select id="order-status-filter" name="status">
                        <option value="">Semua status</option>
                        @foreach ($statusLabels as $status => $label)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="order-filter-actions">
                <button class="admin-primary-button" type="submit">Terapkan filter</button>
                <a class="order-clear-filter" href="{{ route('staff.orders.index', $isQueue ? ['queue' => 1] : []) }}">Hapus filter</a>
            </div>
        </form>

        <section class="order-overview" aria-label="Ringkasan status pesanan">
            @foreach ($statusLabels as $status => $label)
                <a class="order-overview-card {{ $selectedStatus === $status ? 'is-selected' : '' }}" href="{{ route('staff.orders.index', ['status' => $status]) }}">
                    <span>{{ $label }}</span>
                    <strong>{{ $statusCounts[$status] ?? 0 }}</strong>
                </a>
            @endforeach
        </section>

        <section class="order-list" aria-label="Daftar pesanan">
            @forelse ($orders as $order)
                <article class="order-card">
                    <header class="order-card-header">
                        <div>
                            <p class="order-card-number">{{ $order->order_number }} <span>· {{ $order->created_at->format('d/m/Y H:i') }}</span></p>
                            <h2>{{ $order->customer_name ?: 'Pelanggan' }}</h2>
                            @if ($order->customer_phone)
                                <p class="order-card-table">{{ $order->customer_phone }}</p>
                            @endif
                            <p class="order-card-table">
                                {{ $order->diningTable?->label ?? ($order->diningTable ? 'Meja '.$order->diningTable->number : 'Pesanan tanpa meja') }}
                            </p>
                        </div>
                        <span class="order-status-badge status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                    </header>

                    <div class="order-item-list">
                        @foreach ($order->items as $item)
                            <div>
                                <span>{{ $item->quantity }} × {{ $item->product_name }}</span>
                                <strong>Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</strong>
                            </div>
                        @endforeach
                    </div>

                    @if ($order->notes)
                        <p class="order-customer-notes"><strong>Catatan:</strong> {{ $order->notes }}</p>
                    @endif
                    @if ($order->cancel_reason)
                        <p class="order-customer-notes"><strong>Alasan pembatalan:</strong> {{ $order->cancel_reason }}</p>
                    @endif
                    @if ((float) $order->discount_amount > 0)
                        <p class="order-customer-notes">
                            Diskon Rp {{ number_format((float) $order->discount_amount, 0, ',', '.') }}
                            @if ($order->promotion_code)
                                · Voucher {{ $order->promotion_code }}
                            @endif
                            @if ($order->loyalty_points_redeemed > 0)
                                · {{ $order->loyalty_points_redeemed }} poin
                            @endif
                        </p>
                    @endif
                    @if ($order->loyalty_points_earned > 0)
                        <p class="order-customer-notes">Poin diperoleh: {{ $order->loyalty_points_earned }}</p>
                    @endif
                    @if ($order->payment_method)
                        <p class="order-customer-notes">
                            Pembayaran: {{ $paymentMethods[$order->payment_method] ?? $order->payment_method }}
                            @if ($order->change_due && (float) $order->change_due > 0)
                                · Kembalian Rp {{ number_format((float) $order->change_due, 0, ',', '.') }}
                            @endif
                        </p>
                    @endif

                    <footer class="order-card-footer">
                        <strong>Total <span>Rp {{ number_format((float) $order->total, 0, ',', '.') }}</span></strong>
                        @if (isset($allowedTransitions[$order->status]))
                            @php($nextStatus = $allowedTransitions[$order->status])
                            <form method="POST" action="{{ route('staff.orders.update-status', $order) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $nextStatus }}">
                                @if ($nextStatus === 'paid')
                                    <div class="payment-fields">
                                        <label for="payment-method-{{ $order->id }}">Metode pembayaran</label>
                                        <select id="payment-method-{{ $order->id }}" name="payment_method" required>
                                            <option value="">Pilih metode</option>
                                            @foreach ($paymentMethods as $method => $label)
                                                <option value="{{ $method }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <label for="amount-received-{{ $order->id }}">Uang diterima (khusus tunai)</label>
                                        <input
                                            id="amount-received-{{ $order->id }}"
                                            name="amount_received"
                                            type="number"
                                            min="{{ number_format((float) $order->total, 2, '.', '') }}"
                                            step="0.01"
                                            value="{{ old('amount_received') }}"
                                            inputmode="decimal"
                                        >
                                    </div>
                                @endif
                                @if ($isQueue)
                                    <input type="hidden" name="queue" value="1">
                                @endif
                                <button class="admin-primary-button" type="submit">{{ $actionLabels[$nextStatus] }}</button>
                            </form>
                        @endif
                    </footer>
                    @if ($order->status === 'pending_payment')
                        <details class="order-cancel-panel">
                            <summary>Batalkan pesanan</summary>
                            <form method="POST" action="{{ route('staff.orders.cancel', $order) }}">
                                @csrf
                                @method('PATCH')
                                <label for="cancel-reason-{{ $order->id }}">Alasan pembatalan</label>
                                <textarea id="cancel-reason-{{ $order->id }}" name="cancel_reason" rows="2" maxlength="1000" required></textarea>
                                <button class="catalog-delete-button" type="submit">Konfirmasi pembatalan</button>
                            </form>
                        </details>
                    @endif
                </article>
            @empty
                <div class="orders-empty">
                    <span aria-hidden="true">✳</span>
                    <h2>Belum ada pesanan di sini.</h2>
                    <p>Pesanan yang sesuai filter akan muncul di halaman ini.</p>
                </div>
            @endforelse
        </section>

        @if ($orders->hasPages())
            <nav class="order-pagination" aria-label="Navigasi halaman pesanan">
                @if ($orders->onFirstPage())
                    <span aria-disabled="true">← Sebelumnya</span>
                @else
                    <a href="{{ $orders->previousPageUrl() }}">← Sebelumnya</a>
                @endif
                <span>Halaman {{ $orders->currentPage() }} dari {{ $orders->lastPage() }}</span>
                @if ($orders->hasMorePages())
                    <a href="{{ $orders->nextPageUrl() }}">Berikutnya →</a>
                @else
                    <span aria-disabled="true">Berikutnya →</span>
                @endif
            </nav>
        @endif
    </main>
</body>
</html>
