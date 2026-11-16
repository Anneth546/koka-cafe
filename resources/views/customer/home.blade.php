<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    <title>KOKA CAFE | Kopi untuk jeda yang berarti</title>
    <meta name="description" content="Pesan kopi dan sajian KOKA CAFE langsung dari menu.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}">
</head>
<body class="storefront-home">
    @include('customer.partials.navigation')

    <main>
        <section class="hero">
            <div class="hero-copy">
                <p class="eyebrow"><span></span> KOPI, MAKANAN, DAN WAKTU UNTUK DIRI SENDIRI</p>
                <h1>KOKA<br><em>CAFE.</em></h1>
                <p class="hero-description">Ruang kecil untuk jeda besar. Temukan racikan yang pas dan biarkan hari berjalan lebih pelan.</p>
                <a class="button button-light" href="{{ route('customer.menu') }}#menu">Lihat menu <span aria-hidden="true">→</span></a>
                <p class="dine-in-note"><span aria-hidden="true">✳</span>
                    @if ($table)
                        Terhubung ke {{ $table->label ?? 'Meja '.$table->number }}
                    @else
                        Pesan tanpa QR, atau scan QR meja untuk dine-in
                    @endif
                </p>
            </div>
            <div class="hero-index"><span>01</span><i></i><span>03</span></div>
        </section>

        <section class="menu-section" id="menu" data-reveal>
            <div class="section-heading">
                <div>
                    <p class="eyebrow eyebrow-dark">DARI BAR KOKA</p>
                    <h2>Temukan favoritmu<span>.</span></h2>
                </div>
                <p class="section-note">Diracik satu per satu, untuk menemani momenmu hari ini.</p>
            </div>

            <div class="category-nav" aria-label="Filter kategori menu">
                <a class="{{ $categorySlug ? '' : 'is-active' }}" href="{{ route('customer.menu') }}#menu">Semua menu</a>
                @foreach ($categories as $category)
                    <a class="{{ $categorySlug === $category->slug ? 'is-active' : '' }}" href="{{ route('customer.menu', ['kategori' => $category->slug]) }}#menu">
                        {{ $category->name }} <span>{{ $category->products_count }}</span>
                    </a>
                @endforeach
            </div>

            <form class="menu-search" method="GET" action="{{ route('customer.menu') }}#menu" role="search">
                @if ($categorySlug)
                    <input type="hidden" name="kategori" value="{{ $categorySlug }}">
                @endif
                <label class="visually-hidden" for="menu-search">Cari nama menu atau bahan</label>
                <input id="menu-search" name="q" type="search" value="{{ $searchTerm }}" maxlength="80" placeholder="Cari kopi, makanan, atau snack">
                <button type="submit" aria-label="Cari menu">Cari <span aria-hidden="true">⌕</span></button>
            </form>

            @if ($products->isNotEmpty())
                <div class="product-grid" id="pilihan">
                    @foreach ($products as $product)
                        <article class="product-item" data-reveal>
                            <div class="product-photo">
                                @if ($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                                @else
                                    <div class="photo-placeholder" aria-hidden="true">K</div>
                                @endif
                                @if ($product->is_featured)
                                    <span class="product-tag">Pilihan KOKA</span>
                                @endif
                            </div>
                            <div class="product-meta">
                                <span>{{ $product->category->name }}</span>
                                <span class="availability"><i></i> Tersedia</span>
                            </div>
                            <h3>{{ $product->name }}</h3>
                            <p class="product-description">{{ $product->description }}</p>
                            <p class="product-price">Rp {{ number_format((float) $product->price, 0, ',', '.') }}</p>
                            <form method="POST" action="{{ route('cart.items.store') }}">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <button class="add-to-cart" type="submit">Tambah ke keranjang <span aria-hidden="true">+</span></button>
                            </form>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="empty-menu">
                    <span aria-hidden="true">✳</span>
                    <p>Menu pilihan sedang disiapkan.</p>
                    <small>Silakan kembali sebentar lagi.</small>
                </div>
            @endif
        </section>

        <section class="story-section" id="cerita" data-reveal>
            <div class="story-photo" role="img" aria-label="Secangkir kopi di KOKA CAFE"></div>
            <div class="story-copy">
                <p class="eyebrow">SEBUAH JEDA DI TENGAH HARI</p>
                <h2>Rasa yang dekat.<br>Momen yang tinggal.</h2>
                <p>KOKA CAFE menyambutmu untuk duduk sejenak, berbagi cerita, dan menikmati racikan yang dibuat dengan perhatian.</p>
            </div>
            <div class="story-detail"><span>01 / 01</span><p>Setiap pesanan diracik setelah dikonfirmasi di kasir.</p></div>
        </section>

        @if ($reviews->isNotEmpty())
            <section class="customer-reviews-section" aria-labelledby="customer-reviews-heading" data-reveal>
                <div class="section-heading">
                    <div>
                        <p class="eyebrow eyebrow-dark">CERITA PELANGGAN</p>
                        <h2 id="customer-reviews-heading">Kata mereka<span>.</span></h2>
                    </div>
                </div>
                <div class="customer-reviews-grid">
                    @foreach ($reviews as $review)
                        <article class="customer-review-card">
                            <p class="customer-review-rating" aria-label="Nilai {{ $review->rating }} dari 5">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</p>
                            <p>{{ $review->comment ?: 'Terima kasih atas penilaian untuk KOKA CAFE.' }}</p>
                            <strong>{{ $review->customer?->name ?: ($review->order->customer_name ?: 'Pelanggan KOKA') }}</strong>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    <footer class="site-footer">
        <a class="footer-brand" href="{{ route('customer.home') }}">KOKA CAFE</a>
        <p>Temui kami di meja favoritmu.</p>
        <span>© {{ now()->year }} KOKA CAFE</span>
    </footer>
    <script>
        const siteHeader = document.querySelector('.storefront-home .site-header');
        if (siteHeader) {
            const updateHeaderScrollState = () => {
                siteHeader.classList.toggle('is-scrolled', window.scrollY > 16);
            };

            updateHeaderScrollState();
            window.addEventListener('scroll', updateHeaderScrollState, { passive: true });
        }

        if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            const revealObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 });

            document.querySelectorAll('[data-reveal]').forEach((element) => {
                element.classList.add('reveal-on-scroll');
                revealObserver.observe(element);
            });
        }
    </script>
</body>
</html>