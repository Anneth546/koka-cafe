<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    <title>Meja &amp; QR | KOKA CAFE</title>
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
                <h1>Meja &amp; QR.</h1>
                <p class="admin-subtitle">Aktifkan meja dan cetak QR untuk pelanggan dine-in.</p>
            </div>
            <form class="qr-print-form" method="GET" action="{{ route('admin.tables.print') }}" target="_blank">
                <label for="qr-base-url">Alamat QR</label>
                <input id="qr-base-url" name="base_url" type="url" value="{{ request()->getSchemeAndHttpHost() }}" required>
                <button class="admin-primary-button print-qr-button" type="submit">
                    Cetak QR meja <span aria-hidden="true">↗</span>
                </button>
            </form>
        </section>

        @if (session('success'))
            <p class="admin-alert admin-alert-success" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <p class="admin-alert" role="alert">{{ $errors->first() }}</p>
        @endif

        <details class="catalog-create-panel">
            <summary>Tambah meja</summary>
            <form class="catalog-form" method="POST" action="{{ route('admin.tables.store') }}">
                @csrf
                <label>Nomor meja<input name="number" maxlength="24" required></label>
                <label>Label<input name="label" maxlength="80" placeholder="Contoh: Meja Jendela"></label>
                <label>Area<input name="area" maxlength="50" placeholder="Indoor, outdoor, lantai 2"></label>
                <label>Kapasitas<input name="capacity" type="number" min="1" max="255" value="2" required></label>
                <label>Status
                    <select name="status" required>
                        @foreach ($tableStatuses as $status => $label)
                            <option value="{{ $status }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="catalog-checks">
                    <input type="hidden" name="is_active" value="0">
                    <label><input type="checkbox" name="is_active" value="1" checked> Aktif untuk pesanan</label>
                </div>
                <button class="admin-primary-button" type="submit">Simpan meja</button>
            </form>
        </details>

        <section class="table-overview" aria-label="Ringkasan meja">
            <span class="overview-dot"></span>
            <strong>{{ $activeTableCount }}</strong>
            <span>dari {{ $tables->count() }} meja aktif</span>
            <span class="overview-hint">QR mengikuti alamat di form cetak</span>
        </section>

        <section class="admin-table-grid" aria-label="Daftar meja">
            @foreach ($tables as $table)
                <article class="admin-table-card">
                    <div class="table-card-top">
                        <span class="table-card-number">{{ $table->number }}</span>
                        <span class="table-state {{ $table->is_active ? 'state-active' : 'state-inactive' }}">
                            <i></i>{{ $table->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <h2>{{ $table->label ?: 'Meja '.$table->number }}</h2>
                    <p class="table-order-count">{{ $table->area ?: 'Area belum diatur' }} · {{ $table->capacity }} orang · {{ $tableStatuses[$table->status] ?? $table->status }}</p>
                    <p class="table-order-count">{{ $table->orders_count }} pesanan · {{ $table->reservations_count }} reservasi aktif</p>
                    <form method="POST" action="{{ route('admin.tables.update', $table) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="is_active" value="{{ $table->is_active ? 0 : 1 }}">
                        <button class="table-toggle {{ $table->is_active ? '' : 'toggle-enable' }}" type="submit">
                            {{ $table->is_active ? 'Nonaktifkan meja' : 'Aktifkan meja' }}
                        </button>
                    </form>
                    <details class="table-edit-panel">
                        <summary>Edit meja</summary>
                        <form class="catalog-form" method="POST" action="{{ route('admin.tables.update', $table) }}">
                            @csrf
                            @method('PATCH')
                            <label>Nomor meja<input name="number" value="{{ $table->number }}" maxlength="24" required></label>
                            <label>Label<input name="label" value="{{ $table->label }}" maxlength="80"></label>
                            <label>Area<input name="area" value="{{ $table->area }}" maxlength="50"></label>
                            <label>Kapasitas<input name="capacity" type="number" min="1" max="255" value="{{ $table->capacity }}" required></label>
                            <label>Status
                                <select name="status" required>
                                    @foreach ($tableStatuses as $status => $label)
                                        <option value="{{ $status }}" @selected($table->status === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <div class="catalog-checks">
                                <input type="hidden" name="is_active" value="0">
                                <label><input type="checkbox" name="is_active" value="1" @checked($table->is_active)> Aktif untuk pesanan</label>
                            </div>
                            <button class="admin-primary-button" type="submit">Simpan perubahan</button>
                        </form>
                    </details>
                    <form method="POST" action="{{ route('admin.tables.qr.regenerate', $table) }}">
                        @csrf
                        <button class="catalog-delete-button" type="submit">Buat ulang QR</button>
                    </form>
                </article>
            @endforeach
        </section>
    </main>
</body>
</html>