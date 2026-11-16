<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    <title>Kategori Menu | KOKA CAFE</title>
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
                <p class="admin-eyebrow">KATALOG / KATEGORI</p>
                <h1>Kategori menu.</h1>
                <p class="admin-subtitle">Atur nama, deskripsi, status tampil, dan urutan kategori.</p>
            </div>
            <a class="admin-primary-button" href="{{ route('admin.products.index') }}">Kembali ke produk</a>
        </section>

        @if (session('success'))
            <p class="admin-alert admin-alert-success" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <p class="admin-alert" role="alert">{{ $errors->first() }}</p>
        @endif

        <details class="catalog-create-panel" open>
            <summary>Tambah kategori</summary>
            <form class="catalog-form" method="POST" action="{{ route('admin.categories.store') }}">
                @csrf
                <label>Nama kategori<input name="name" value="{{ old('name') }}" maxlength="100" required></label>
                <label>Urutan tampil<input name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', 0) }}"></label>
                <label class="catalog-form-wide">Deskripsi<textarea name="description" rows="3" maxlength="2000">{{ old('description') }}</textarea></label>
                <div class="catalog-checks">
                    <label><input type="checkbox" name="is_active" value="1" checked> Tampilkan ke pelanggan</label>
                </div>
                <button class="admin-primary-button" type="submit">Simpan kategori</button>
            </form>
        </details>

        <section class="category-list" aria-label="Daftar kategori">
            @forelse ($categories as $category)
                <article class="category-card">
                    <div class="category-card-heading">
                        <div>
                            <p class="admin-eyebrow">URUTAN {{ $category->sort_order }}</p>
                            <h2>{{ $category->name }}</h2>
                            <p>{{ $category->products_count }} produk · {{ $category->is_active ? 'Tampil ke pelanggan' : 'Nonaktif' }}</p>
                        </div>
                        <details>
                            <summary>Edit</summary>
                            <form class="catalog-form" method="POST" action="{{ route('admin.categories.update', $category) }}">
                                @csrf
                                @method('PUT')
                                <label>Nama kategori<input name="name" value="{{ $category->name }}" maxlength="100" required></label>
                                <label>Urutan tampil<input name="sort_order" type="number" min="0" max="65535" value="{{ $category->sort_order }}"></label>
                                <label class="catalog-form-wide">Deskripsi<textarea name="description" rows="3" maxlength="2000">{{ $category->description }}</textarea></label>
                                <div class="catalog-checks">
                                    <input type="hidden" name="is_active" value="0">
                                    <label><input type="checkbox" name="is_active" value="1" @checked($category->is_active)> Tampilkan ke pelanggan</label>
                                </div>
                                <button class="admin-primary-button" type="submit">Simpan perubahan</button>
                            </form>
                        </details>
                    </div>
                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}">
                        @csrf
                        @method('DELETE')
                        <button class="catalog-delete-button" type="submit">Hapus kategori</button>
                    </form>
                </article>
            @empty
                <div class="orders-empty">
                    <h2>Belum ada kategori.</h2>
                    <p>Tambahkan kategori untuk mulai mengelola menu.</p>
                </div>
            @endforelse
        </section>
    </main>
</body>
</html>
