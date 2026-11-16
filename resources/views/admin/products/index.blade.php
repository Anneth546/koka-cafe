<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    <title>Menu &amp; Produk | KOKA CAFE</title>
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
                <p class="admin-eyebrow">KATALOG / PRODUK</p>
                <h1>Menu &amp; produk.</h1>
                <p class="admin-subtitle">Atur nama, harga, foto, kategori, ketersediaan, dan menu favorit.</p>
            </div>
            <a class="admin-primary-button" href="{{ route('admin.categories.index') }}">Kelola kategori</a>
        </section>

        @if (session('success'))
            <p class="admin-alert admin-alert-success" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <p class="admin-alert" role="alert">{{ $errors->first() }}</p>
        @endif

        @if ($categories->isEmpty())
            <p class="admin-alert" role="alert">Buat kategori terlebih dahulu sebelum menambahkan produk.</p>
        @else
            <details class="catalog-create-panel" open>
                <summary>Tambah produk</summary>
                <form class="catalog-form" method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
                    @csrf
                    <label>Nama produk<input name="name" value="{{ old('name') }}" maxlength="140" required></label>
                    <label>Kategori
                        <select name="category_id" required>
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (nonaktif)' }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Harga (Rp)<input name="price" type="number" min="1" step="0.01" value="{{ old('price') }}" required></label>
                    <label>URL foto (opsional)<input name="image_url" type="url" value="{{ old('image_url') }}" placeholder="https://..."></label>
                    <label class="catalog-form-wide">Unggah foto (JPG, PNG, WebP; maks. 2 MB)<input name="image_file" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></label>
                    <label class="catalog-form-wide">Deskripsi<textarea name="description" rows="3" maxlength="5000">{{ old('description') }}</textarea></label>
                    <div class="catalog-checks">
                        <label><input type="checkbox" name="is_available" value="1" @checked(old('is_available', true))> Tersedia</label>
                        <label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured'))> Tampilkan sebagai favorit</label>
                    </div>
                    <button class="admin-primary-button" type="submit">Simpan produk</button>
                </form>
            </details>
        @endif

        <section class="catalog-product-list" aria-label="Daftar produk">
            @forelse ($products as $product)
                <article class="catalog-product-card">
                    <div class="catalog-product-summary">
                        @if ($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                        @else
                            <div class="catalog-product-placeholder">KOKA</div>
                        @endif
                        <div>
                            <p class="admin-eyebrow">{{ $product->category->name }}</p>
                            <h2>{{ $product->name }}</h2>
                            <p>Rp {{ number_format((float) $product->price, 0, ',', '.') }}</p>
                            <div class="catalog-badges">
                                <span class="{{ $product->is_available ? 'catalog-badge-active' : 'catalog-badge-inactive' }}">{{ $product->is_available ? 'Tersedia' : 'Habis' }}</span>
                                @if (! $product->hasStockForRecipe())
                                    <span class="inventory-low-stock">Bahan resep habis</span>
                                @endif
                                @if ($product->is_featured)
                                    <span class="catalog-badge-featured">Favorit</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <details class="catalog-edit-panel">
                        <summary>Edit produk</summary>
                        <form class="catalog-form" method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <label>Nama produk<input name="name" value="{{ $product->name }}" maxlength="140" required></label>
                            <label>Kategori
                                <select name="category_id" required>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected($product->category_id === $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (nonaktif)' }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Harga (Rp)<input name="price" type="number" min="1" step="0.01" value="{{ $product->price }}" required></label>
                            @if ($product->image_url && str_starts_with($product->image_url, 'http'))
                                <label>URL foto<input name="image_url" type="url" value="{{ $product->image_url }}"></label>
                            @else
                                <input name="image_url" type="hidden" value="">
                            @endif
                            <label class="catalog-form-wide">Ganti foto (JPG, PNG, WebP; maks. 2 MB)<input name="image_file" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></label>
                            @if ($product->image_url)
                                <label class="catalog-form-wide"><input type="checkbox" name="remove_image" value="1"> Hapus foto saat ini</label>
                            @endif
                            <label class="catalog-form-wide">Deskripsi<textarea name="description" rows="3" maxlength="5000">{{ $product->description }}</textarea></label>
                            <div class="catalog-checks">
                                <label><input type="checkbox" name="is_available" value="1" @checked($product->is_available)> Tersedia</label>
                                <label><input type="checkbox" name="is_featured" value="1" @checked($product->is_featured)> Tampilkan sebagai favorit</label>
                            </div>
                            <button class="admin-primary-button" type="submit">Simpan perubahan</button>
                        </form>
                    </details>
                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}">
                        @csrf
                        @method('DELETE')
                        <button class="catalog-delete-button" type="submit">Hapus produk</button>
                    </form>
                </article>
            @empty
                <div class="orders-empty">
                    <h2>Belum ada produk.</h2>
                    <p>Produk yang ditambahkan akan muncul di sini.</p>
                </div>
            @endforelse
        </section>

        @if ($products->hasPages())
            <nav class="order-pagination" aria-label="Navigasi halaman produk">
                {{ $products->links() }}
            </nav>
        @endif
    </main>
</body>
</html>
