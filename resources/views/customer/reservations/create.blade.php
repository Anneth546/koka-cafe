<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    <title>Reservasi Meja | KOKA CAFE</title>
    <meta name="description" content="Reservasi meja di KOKA CAFE.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}">
</head>
<body>
    @include('customer.partials.navigation')

    <main class="reservation-page">
        <section class="reservation-heading">
            <p class="eyebrow eyebrow-dark">WAKTU UNTUK BERTEMU</p>
            <h1>Reservasi meja<span>.</span></h1>
            <p class="reservation-lead">Pilih waktu dan meja favoritmu. Detail reservasi akan tercatat di dashboard admin KOKA CAFE.</p>
        </section>

        @if (session('success'))
            <p class="notice notice-success" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <div class="notice notice-error" role="alert">
                <strong>Periksa kembali data reservasi:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="reservation-card" aria-label="Formulir reservasi meja">
            @if ($tables->isEmpty())
                <div class="reservation-empty">
                    <h2>Meja belum tersedia.</h2>
                    <p>Silakan hubungi KOKA CAFE untuk bantuan reservasi.</p>
                </div>
            @else
                <form class="reservation-form" method="POST" action="{{ route('customer.reservations.store') }}">
                    @csrf
                    <label>Nama lengkap
                        <input name="customer_name" value="{{ old('customer_name') }}" maxlength="100" autocomplete="name" required>
                    </label>
                    <label>Nomor HP
                        <input name="customer_phone" type="tel" inputmode="tel" value="{{ old('customer_phone') }}" maxlength="24" autocomplete="tel" required>
                    </label>
                    <label>Pilih meja
                        <select name="dining_table_id" required>
                            <option value="">Pilih meja yang tersedia</option>
                            @foreach ($tables as $table)
                                <option value="{{ $table->id }}" @selected((string) old('dining_table_id') === (string) $table->id)>
                                    {{ $table->label ?: 'Meja '.$table->number }} · {{ $table->area ?: 'Area cafe' }} · {{ $table->capacity }} orang
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label>Jumlah tamu
                        <input name="party_size" type="number" min="1" max="30" value="{{ old('party_size', 2) }}" required>
                    </label>
                    <label>Waktu mulai
                        <input name="starts_at" type="datetime-local" value="{{ old('starts_at', now()->addHour()->format('Y-m-d\TH:i')) }}" required>
                    </label>
                    <label>Waktu selesai
                        <input name="ends_at" type="datetime-local" value="{{ old('ends_at', now()->addHours(3)->format('Y-m-d\TH:i')) }}" required>
                    </label>
                    <label class="reservation-form-wide">Catatan (opsional)
                        <textarea name="notes" rows="3" maxlength="1000" placeholder="Contoh: ingin duduk di area outdoor">{{ old('notes') }}</textarea>
                    </label>
                    <p class="reservation-help reservation-form-wide">Meja dan kapasitas akan dicek otomatis. Admin dapat melihat reservasi setelah formulir dikirim.</p>
                    <button class="button button-dark reservation-submit reservation-form-wide" type="submit">Kirim reservasi <span aria-hidden="true">→</span></button>
                </form>
            @endif
        </section>
    </main>

    <footer class="site-footer">
        <a class="footer-brand" href="{{ route('customer.home') }}">KOKA CAFE</a>
        <p>Temui kami di meja favoritmu.</p>
        <span>© {{ now()->year }} KOKA CAFE</span>
    </footer>
</body>
</html>
