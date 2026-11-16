<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    <title>Inventaris | KOKA CAFE</title>
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
                <p class="admin-eyebrow">OPERASIONAL / STOK</p>
                <h1>Inventaris bahan.</h1>
                <p class="admin-subtitle">Catat stok bahan, pemasok, resep produk, dan pemakaian otomatis saat pembayaran dikonfirmasi.</p>
            </div>
        </section>

        @if (session('success'))
            <p class="admin-alert admin-alert-success" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <p class="admin-alert" role="alert">{{ $errors->first() }}</p>
        @endif

        <section class="dashboard-grid inventory-summary" aria-label="Ringkasan inventaris">
            <article class="dashboard-card">
                <span>Bahan aktif</span>
                <strong>{{ number_format($activeIngredientCount, 0, ',', '.') }}</strong>
                <small>Bahan yang dipakai dalam resep</small>
            </article>
            <article class="dashboard-card">
                <span>Stok menipis</span>
                <strong>{{ number_format($lowStockCount, 0, ',', '.') }}</strong>
                <small>Stok sama dengan atau di bawah batas minimum</small>
            </article>
            <article class="dashboard-card">
                <span>Pemasok</span>
                <strong>{{ number_format($suppliers->where('is_active', true)->count(), 0, ',', '.') }}</strong>
                <small>Pemasok aktif</small>
            </article>
        </section>

        <section class="inventory-actions" aria-label="Aksi inventaris">
            <details class="catalog-create-panel">
                <summary>Tambah bahan baku</summary>
                <form class="catalog-form" method="POST" action="{{ route('admin.inventory.ingredients.store') }}">
                    @csrf
                    <label>Nama bahan<input name="name" value="{{ old('name') }}" maxlength="120" required></label>
                    <label>Satuan dasar<input name="unit" value="{{ old('unit') }}" maxlength="16" placeholder="g, ml, pcs" required></label>
                    <label>Batas stok minimum<input name="low_stock_threshold" type="number" min="0" step="0.001" value="{{ old('low_stock_threshold', 0) }}" required></label>
                    <label>Harga pokok per satuan<input name="unit_cost" type="number" min="0" step="0.01" value="{{ old('unit_cost', 0) }}" required></label>
                    <label>Pemasok utama
                        <select name="supplier_id">
                            <option value="">Belum ditentukan</option>
                            @foreach ($suppliers->where('is_active', true) as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="catalog-checks">
                        <input type="hidden" name="is_active" value="0">
                        <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Aktif</label>
                    </div>
                    <button class="admin-primary-button" type="submit">Simpan bahan</button>
                </form>
            </details>

            <details class="catalog-create-panel">
                <summary>Catat stok masuk, terbuang, atau stok opname</summary>
                <form class="catalog-form" method="POST" action="{{ route('admin.inventory.movements.store') }}">
                    @csrf
                    <label>Bahan
                        <select name="ingredient_id" required>
                            <option value="">Pilih bahan</option>
                            @foreach ($recipeIngredients as $ingredient)
                                <option value="{{ $ingredient->id }}" @selected((string) old('ingredient_id') === (string) $ingredient->id)>
                                    {{ $ingredient->name }} ({{ $ingredient->stock_on_hand }} {{ $ingredient->unit }})
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label>Jenis catatan
                        <select name="type" required>
                            <option value="purchase" @selected(old('type') === 'purchase')>Stok masuk / pembelian</option>
                            <option value="waste" @selected(old('type') === 'waste')>Stok terbuang / rusak</option>
                            <option value="adjustment" @selected(old('type') === 'adjustment')>Stok opname (jumlah aktual)</option>
                        </select>
                    </label>
                    <label>Jumlah
                        <input name="quantity" type="number" min="0" step="0.001" value="{{ old('quantity') }}" required>
                        <small class="inventory-field-hint">Stok opname memakai jumlah aktual; pembelian dan stok terbuang memakai jumlah perubahan.</small>
                    </label>
                    <label>Pemasok
                        <select name="supplier_id">
                            <option value="">Tidak dicatat</option>
                            @foreach ($suppliers->where('is_active', true) as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Harga beli per satuan<input name="unit_cost" type="number" min="0" step="0.01" value="{{ old('unit_cost') }}"></label>
                    <label class="catalog-form-wide">Catatan<textarea name="notes" rows="2" maxlength="1000">{{ old('notes') }}</textarea></label>
                    <button class="admin-primary-button" type="submit">Simpan pergerakan stok</button>
                </form>
            </details>

            <details class="catalog-create-panel">
                <summary>Tambah pemasok</summary>
                <form class="catalog-form" method="POST" action="{{ route('admin.inventory.suppliers.store') }}">
                    @csrf
                    <label>Nama pemasok<input name="name" value="{{ old('name') }}" maxlength="120" required></label>
                    <label>Nomor telepon<input name="phone" type="tel" value="{{ old('phone') }}" maxlength="24"></label>
                    <label>Email<input name="email" type="email" value="{{ old('email') }}" maxlength="255"></label>
                    <label class="catalog-form-wide">Alamat / catatan<textarea name="address" rows="2" maxlength="2000">{{ old('address') }}</textarea></label>
                    <button class="admin-primary-button" type="submit">Simpan pemasok</button>
                </form>
            </details>
        </section>

        <section class="inventory-section" aria-labelledby="inventory-ingredients-heading">
            <div class="inventory-section-heading">
                <div>
                    <p class="admin-eyebrow">BAHAN BAKU</p>
                    <h2 id="inventory-ingredients-heading">Daftar stok.</h2>
                </div>
            </div>
            <div class="inventory-list">
                @forelse ($ingredients as $ingredient)
                    <article class="inventory-card">
                        <div class="inventory-card-heading">
                            <div>
                                <p class="admin-eyebrow">{{ $ingredient->supplier?->name ?: 'Pemasok belum ditentukan' }}</p>
                                <h3>{{ $ingredient->name }}</h3>
                            </div>
                            @if ($ingredient->is_active && (float) $ingredient->stock_on_hand <= (float) $ingredient->low_stock_threshold)
                                <span class="inventory-low-stock">Stok menipis</span>
                            @elseif (! $ingredient->is_active)
                                <span class="reservation-status reservation-status-cancelled">Nonaktif</span>
                            @else
                                <span class="reservation-status reservation-status-seated">Aktif</span>
                            @endif
                        </div>
                        <div class="inventory-stock-values">
                            <span><small>Stok</small><strong>{{ $ingredient->stock_on_hand }} {{ $ingredient->unit }}</strong></span>
                            <span><small>Batas minimum</small><strong>{{ $ingredient->low_stock_threshold }} {{ $ingredient->unit }}</strong></span>
                            <span><small>HPP / satuan</small><strong>Rp {{ number_format((float) $ingredient->unit_cost, 2, ',', '.') }}</strong></span>
                        </div>
                        <details class="table-edit-panel">
                            <summary>Edit bahan</summary>
                            <form class="catalog-form" method="POST" action="{{ route('admin.inventory.ingredients.update', $ingredient) }}">
                                @csrf
                                @method('PATCH')
                                <label>Nama bahan<input name="name" value="{{ $ingredient->name }}" maxlength="120" required></label>
                                <label>Satuan dasar<input name="unit" value="{{ $ingredient->unit }}" maxlength="16" required></label>
                                <label>Batas stok minimum<input name="low_stock_threshold" type="number" min="0" step="0.001" value="{{ $ingredient->low_stock_threshold }}" required></label>
                                <label>Harga pokok per satuan<input name="unit_cost" type="number" min="0" step="0.01" value="{{ $ingredient->unit_cost }}" required></label>
                                <label>Pemasok utama
                                    <select name="supplier_id">
                                        <option value="">Belum ditentukan</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" @selected($ingredient->supplier_id === $supplier->id)>{{ $supplier->name }}{{ $supplier->is_active ? '' : ' (nonaktif)' }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <div class="catalog-checks">
                                    <input type="hidden" name="is_active" value="0">
                                    <label><input type="checkbox" name="is_active" value="1" @checked($ingredient->is_active)> Aktif</label>
                                </div>
                                <button class="admin-primary-button" type="submit">Simpan perubahan</button>
                            </form>
                        </details>
                    </article>
                @empty
                    <div class="orders-empty">
                        <h2>Belum ada bahan baku.</h2>
                        <p>Tambahkan bahan untuk mulai mencatat persediaan dan resep.</p>
                    </div>
                @endforelse
            </div>
            @if ($ingredients->hasPages())
                <nav class="order-pagination" aria-label="Navigasi halaman bahan">
                    {{ $ingredients->links() }}
                </nav>
            @endif
        </section>

        <section class="inventory-section" aria-labelledby="inventory-recipes-heading">
            <div class="inventory-section-heading">
                <div>
                    <p class="admin-eyebrow">RESEP PRODUK</p>
                    <h2 id="inventory-recipes-heading">Pemakaian bahan per menu.</h2>
                </div>
            </div>
            <form class="recipe-product-picker" method="GET" action="{{ route('admin.inventory.index') }}">
                <label for="inventory-product">Pilih produk untuk mengatur resep</label>
                <select id="inventory-product" name="product_id" required>
                    <option value="">Pilih produk</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected($selectedProduct?->id === $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
                <button class="admin-primary-button" type="submit">Buka resep</button>
            </form>
            @if ($selectedProduct)
                @if ($recipeIngredients->isEmpty())
                    <p class="report-empty">Tambahkan bahan baku aktif sebelum mengatur resep.</p>
                @else
                    <p class="inventory-field-hint">Kosongkan jumlah bahan yang tidak dipakai. Satuan resep mengikuti satuan dasar bahan.</p>
                    <form class="recipe-editor" method="POST" action="{{ route('admin.inventory.recipes.update', $selectedProduct) }}">
                        @csrf
                        @method('PUT')
                        @foreach ($recipeIngredients as $ingredient)
                            @php
                                $recipeIngredient = $selectedProduct->ingredients->firstWhere('id', $ingredient->id);
                            @endphp
                            <label>
                                <span><strong>{{ $ingredient->name }}</strong><small>Stok {{ $ingredient->stock_on_hand }} {{ $ingredient->unit }}</small></span>
                                <input name="ingredients[{{ $ingredient->id }}]" type="number" min="0.001" step="0.001" value="{{ old('ingredients.'.$ingredient->id, $recipeIngredient?->pivot->quantity) }}" placeholder="Tidak digunakan">
                            </label>
                        @endforeach
                        <button class="admin-primary-button" type="submit">Simpan resep {{ $selectedProduct->name }}</button>
                    </form>
                @endif
            @else
                <p class="report-empty">Pilih produk untuk menambahkan atau mengubah resepnya.</p>
            @endif
        </section>

        <section class="inventory-section" aria-labelledby="inventory-suppliers-heading">
            <div class="inventory-section-heading">
                <div>
                    <p class="admin-eyebrow">PEMASOK</p>
                    <h2 id="inventory-suppliers-heading">Daftar pemasok.</h2>
                </div>
            </div>
            <div class="inventory-list">
                @forelse ($suppliers as $supplier)
                    <article class="inventory-card">
                        <div class="inventory-card-heading">
                            <div>
                                <h3>{{ $supplier->name }}</h3>
                                <p>{{ $supplier->phone ?: 'Nomor telepon belum dicatat' }}{{ $supplier->email ? ' · '.$supplier->email : '' }}</p>
                            </div>
                            <span class="reservation-status {{ $supplier->is_active ? 'reservation-status-seated' : 'reservation-status-cancelled' }}">{{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                        <details class="table-edit-panel">
                            <summary>Edit pemasok</summary>
                            <form class="catalog-form" method="POST" action="{{ route('admin.inventory.suppliers.update', $supplier) }}">
                                @csrf
                                @method('PUT')
                                <label>Nama pemasok<input name="name" value="{{ $supplier->name }}" maxlength="120" required></label>
                                <label>Nomor telepon<input name="phone" type="tel" value="{{ $supplier->phone }}" maxlength="24"></label>
                                <label>Email<input name="email" type="email" value="{{ $supplier->email }}" maxlength="255"></label>
                                <label class="catalog-form-wide">Alamat / catatan<textarea name="address" rows="2" maxlength="2000">{{ $supplier->address }}</textarea></label>
                                <div class="catalog-checks">
                                    <input type="hidden" name="is_active" value="0">
                                    <label><input type="checkbox" name="is_active" value="1" @checked($supplier->is_active)> Aktif</label>
                                </div>
                                <button class="admin-primary-button" type="submit">Simpan perubahan</button>
                            </form>
                        </details>
                    </article>
                @empty
                    <div class="orders-empty"><h2>Belum ada pemasok.</h2></div>
                @endforelse
            </div>
        </section>

        <section class="inventory-section" aria-labelledby="inventory-movements-heading">
            <div class="inventory-section-heading">
                <div>
                    <p class="admin-eyebrow">RIWAYAT STOK</p>
                    <h2 id="inventory-movements-heading">Pergerakan terbaru.</h2>
                </div>
            </div>
            <div class="report-table-wrap">
                <table class="report-table">
                    <thead><tr><th>Waktu</th><th>Bahan</th><th>Jenis</th><th>Perubahan</th><th>Pemasok / pesanan</th><th>Catatan</th></tr></thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            <tr>
                                <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $movement->ingredient->name }} ({{ $movement->ingredient->unit }})</td>
                                <td>{{ ['purchase' => 'Pembelian', 'usage' => 'Pemakaian', 'waste' => 'Terbuang', 'adjustment' => 'Stok opname'][$movement->type] ?? $movement->type }}</td>
                                <td>{{ $movement->quantity }} {{ $movement->ingredient->unit }}</td>
                                <td>{{ $movement->supplier?->name ?: ($movement->order?->order_number ?: '—') }}</td>
                                <td>{{ $movement->notes ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">Belum ada pergerakan stok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($movements->hasPages())
                <nav class="order-pagination" aria-label="Navigasi halaman riwayat stok">
                    {{ $movements->links() }}
                </nav>
            @endif
        </section>
    </main>
</body>
</html>
