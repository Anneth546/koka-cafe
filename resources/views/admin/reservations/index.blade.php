<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    <title>Reservasi | KOKA CAFE</title>
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
                <p class="admin-eyebrow">OPERASIONAL / MEJA</p>
                <h1>Reservasi meja.</h1>
                <p class="admin-subtitle">Atur reservasi dan hindari jadwal bentrok untuk meja yang sama.</p>
            </div>
        </section>

        @if (session('success'))
            <p class="admin-alert admin-alert-success" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <p class="admin-alert" role="alert">{{ $errors->first() }}</p>
        @endif

        <details class="catalog-create-panel" open>
            <summary>Tambah reservasi</summary>
            @if ($tables->isEmpty())
                <p class="report-empty">Aktifkan meja terlebih dahulu untuk menerima reservasi.</p>
            @else
                <form class="catalog-form" method="POST" action="{{ route('admin.reservations.store') }}">
                    @csrf
                    <label>Nama pelanggan<input name="customer_name" value="{{ old('customer_name') }}" maxlength="100" required></label>
                    <label>Nomor HP<input name="customer_phone" type="tel" value="{{ old('customer_phone') }}" maxlength="24" required></label>
                    <label>Meja
                        <select name="dining_table_id" required>
                            <option value="">Pilih meja</option>
                            @foreach ($tables as $table)
                                <option value="{{ $table->id }}" @selected((string) old('dining_table_id') === (string) $table->id)>
                                    {{ $table->label ?: 'Meja '.$table->number }} · {{ $table->capacity }} orang
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label>Jumlah tamu<input name="party_size" type="number" min="1" max="30" value="{{ old('party_size', 2) }}" required></label>
                    <label>Waktu mulai<input name="starts_at" type="datetime-local" value="{{ old('starts_at', now()->addHour()->format('Y-m-d\TH:i')) }}" required></label>
                    <label>Waktu selesai<input name="ends_at" type="datetime-local" value="{{ old('ends_at', now()->addHours(3)->format('Y-m-d\TH:i')) }}" required></label>
                    <label class="catalog-form-wide">Catatan<textarea name="notes" rows="2" maxlength="1000">{{ old('notes') }}</textarea></label>
                    <button class="admin-primary-button" type="submit">Simpan reservasi</button>
                </form>
            @endif
        </details>

        <section class="reservation-list" aria-label="Daftar reservasi">
            @forelse ($reservations as $reservation)
                <article class="reservation-card">
                    <div class="reservation-card-heading">
                        <div>
                            <p class="admin-eyebrow">{{ $reservation->starts_at->format('d/m/Y H:i') }}–{{ $reservation->ends_at->format('H:i') }}</p>
                            <h2>{{ $reservation->customer_name }}</h2>
                            <p>{{ $reservation->customer_phone }} · {{ $reservation->party_size }} tamu</p>
                            <p>{{ $reservation->diningTable?->label ?: 'Meja '.$reservation->diningTable?->number }}</p>
                            @if ($reservation->notes)
                                <p>{{ $reservation->notes }}</p>
                            @endif
                        </div>
                        <span class="reservation-status reservation-status-{{ $reservation->status }}">{{ $statusLabels[$reservation->status] ?? $reservation->status }}</span>
                    </div>
                    @if (in_array($reservation->status, ['confirmed', 'seated'], true))
                        <div class="reservation-actions">
                            @if ($reservation->status === 'confirmed')
                                <form method="POST" action="{{ route('admin.reservations.update-status', $reservation) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="seated">
                                    <button class="table-toggle" type="submit">Tamu datang</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.reservations.update-status', $reservation) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="completed">
                                    <button class="table-toggle" type="submit">Selesaikan</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.reservations.update-status', $reservation) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="cancelled">
                                <button class="catalog-delete-button" type="submit">Batalkan</button>
                            </form>
                        </div>
                    @endif
                </article>
            @empty
                <div class="orders-empty">
                    <h2>Belum ada reservasi.</h2>
                    <p>Reservasi yang dibuat akan muncul di sini.</p>
                </div>
            @endforelse
        </section>

        @if ($reservations->hasPages())
            <nav class="order-pagination" aria-label="Navigasi halaman reservasi">
                {{ $reservations->links() }}
            </nav>
        @endif
    </main>
</body>
</html>
