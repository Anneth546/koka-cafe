<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    <title>Voucher dan loyalitas | KOKA CAFE</title>
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
                <p class="admin-eyebrow">PELANGGAN / PROMO</p>
                <h1>Voucher dan loyalitas.</h1>
                <p class="admin-subtitle">Atur diskon voucher dan program poin pelanggan KOKA CAFE.</p>
            </div>
        </section>

        @if (session('success'))
            <p class="admin-alert admin-alert-success" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <div class="admin-alert" role="alert">{{ $errors->first() }}</div>
        @endif

        <section class="report-panel promotion-panel" aria-labelledby="create-promotion-heading">
            <h2 id="create-promotion-heading" class="promotion-section-title">Buat voucher</h2>
            <p class="promotion-copy">Diskon voucher dan penukaran poin dapat digunakan bersamaan. Satu voucher berlaku untuk setiap pesanan.</p>
            <form class="catalog-form promotion-form" method="POST" action="{{ route('admin.promotions.store') }}">
                @csrf
                <input type="hidden" name="is_active" value="0">
                <label>Kode voucher
                    <input name="code" maxlength="40" value="{{ old('code') }}" required pattern="[A-Za-z0-9_-]+" autocomplete="off">
                </label>
                <label>Nama promo
                    <input name="name" maxlength="120" value="{{ old('name') }}" required>
                </label>
                <label>Jenis diskon
                    <select name="discount_type" required>
                        <option value="fixed" @selected(old('discount_type', 'fixed') === 'fixed')>Nominal rupiah</option>
                        <option value="percentage" @selected(old('discount_type') === 'percentage')>Persentase</option>
                    </select>
                </label>
                <label>Nilai diskon
                    <input name="discount_value" type="number" min="0.01" step="0.01" value="{{ old('discount_value') }}" required>
                </label>
                <label>Minimum belanja (Rp)
                    <input name="minimum_order" type="number" min="0" step="0.01" value="{{ old('minimum_order', '0') }}" required>
                </label>
                <label>Maksimum diskon (Rp, opsional)
                    <input name="maximum_discount" type="number" min="0.01" step="0.01" value="{{ old('maximum_discount') }}">
                </label>
                <label>Batas pemakaian (opsional)
                    <input name="usage_limit" type="number" min="1" step="1" value="{{ old('usage_limit') }}">
                </label>
                <label>Mulai berlaku
                    <input name="starts_at" type="datetime-local" value="{{ old('starts_at') }}">
                </label>
                <label>Berakhir
                    <input name="ends_at" type="datetime-local" value="{{ old('ends_at') }}">
                </label>
                <div class="catalog-checks catalog-form-wide">
                    <label>
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')>
                        Aktif
                    </label>
                </div>
                <button class="admin-primary-button" type="submit">Simpan voucher <span aria-hidden="true">→</span></button>
            </form>
        </section>

        <section class="promotion-section" aria-labelledby="promotion-list-heading">
            <div class="inventory-section-heading">
                <div>
                    <p class="admin-eyebrow">KODE DAN STATUS PROMO</p>
                    <h2 id="promotion-list-heading">Voucher terdaftar.</h2>
                </div>
            </div>
            @if ($promotions->isEmpty())
                <div class="orders-empty">
                    <span aria-hidden="true">✳</span>
                    <h2>Belum ada voucher.</h2>
                    <p>Voucher yang dibuat akan tampil di sini.</p>
                </div>
            @else
                <div class="promotion-list">
                    @foreach ($promotions as $promotion)
                        <article class="promotion-card">
                            <div class="promotion-heading">
                                <div>
                                    <p class="admin-eyebrow">{{ $promotion->code }}</p>
                                    <h3>{{ $promotion->name }}</h3>
                                    <p class="promotion-usage">
                                        Digunakan {{ $promotion->usage_count }}
                                        @if ($promotion->usage_limit !== null)
                                            dari {{ $promotion->usage_limit }} kali
                                        @else
                                            kali
                                        @endif
                                    </p>
                                </div>
                                <span class="promotion-status {{ $promotion->is_active ? 'promotion-status-active' : 'promotion-status-inactive' }}">
                                    {{ $promotion->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                            <form class="catalog-form promotion-form" method="POST" action="{{ route('admin.promotions.update', $promotion) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="is_active" value="0">
                                <label>Kode voucher
                                    <input name="code" maxlength="40" value="{{ $promotion->code }}" required pattern="[A-Za-z0-9_-]+">
                                </label>
                                <label>Nama promo
                                    <input name="name" maxlength="120" value="{{ $promotion->name }}" required>
                                </label>
                                <label>Jenis diskon
                                    <select name="discount_type" required>
                                        <option value="fixed" @selected($promotion->discount_type === 'fixed')>Nominal rupiah</option>
                                        <option value="percentage" @selected($promotion->discount_type === 'percentage')>Persentase</option>
                                    </select>
                                </label>
                                <label>Nilai diskon
                                    <input name="discount_value" type="number" min="0.01" step="0.01" value="{{ $promotion->discount_value }}" required>
                                </label>
                                <label>Minimum belanja (Rp)
                                    <input name="minimum_order" type="number" min="0" step="0.01" value="{{ $promotion->minimum_order }}" required>
                                </label>
                                <label>Maksimum diskon (Rp, opsional)
                                    <input name="maximum_discount" type="number" min="0.01" step="0.01" value="{{ $promotion->maximum_discount }}">
                                </label>
                                <label>Batas pemakaian (opsional)
                                    <input name="usage_limit" type="number" min="1" step="1" value="{{ $promotion->usage_limit }}">
                                </label>
                                <label>Mulai berlaku
                                    <input name="starts_at" type="datetime-local" value="{{ $promotion->starts_at?->format('Y-m-d\TH:i') }}">
                                </label>
                                <label>Berakhir
                                    <input name="ends_at" type="datetime-local" value="{{ $promotion->ends_at?->format('Y-m-d\TH:i') }}">
                                </label>
                                <div class="catalog-checks catalog-form-wide">
                                    <label>
                                        <input type="checkbox" name="is_active" value="1" @checked($promotion->is_active)>
                                        Aktif
                                    </label>
                                </div>
                                <button class="admin-primary-button" type="submit">Perbarui voucher <span aria-hidden="true">→</span></button>
                            </form>
                        </article>
                    @endforeach
                </div>
                <div class="promotion-pagination">{{ $promotions->links() }}</div>
            @endif
        </section>
    </main>
</body>
</html>
