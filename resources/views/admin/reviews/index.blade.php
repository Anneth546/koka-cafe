<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    <title>Ulasan | KOKA CAFE</title>
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
                <p class="admin-eyebrow">PELANGGAN / ULASAN</p>
                <h1>Ulasan pelanggan.</h1>
                <p class="admin-subtitle">Ulasan baru ditahan sampai admin memeriksa dan mempublikasikannya.</p>
            </div>
        </section>

        @if (session('success'))
            <p class="admin-alert admin-alert-success" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <p class="admin-alert" role="alert">{{ $errors->first() }}</p>
        @endif

        <section class="dashboard-grid inventory-summary" aria-label="Ringkasan ulasan">
            <article class="dashboard-card">
                <span>Menunggu moderasi</span>
                <strong>{{ number_format($pendingReviewCount, 0, ',', '.') }}</strong>
                <small>Belum tampil ke publik</small>
            </article>
            <article class="dashboard-card">
                <span>Rata-rata nilai publik</span>
                <strong>{{ $averageRating === null ? '—' : number_format((float) $averageRating, 1, ',', '.') }}</strong>
                <small>Dari ulasan yang dipublikasikan</small>
            </article>
        </section>

        <section class="review-admin-list" aria-label="Daftar ulasan">
            @forelse ($reviews as $review)
                <article class="review-admin-card">
                    <div class="review-admin-heading">
                        <div>
                            <p class="admin-eyebrow">{{ $review->created_at->format('d/m/Y H:i') }} · {{ $review->order->order_number }}</p>
                            <h2>{{ $review->customer?->name ?: ($review->order->customer_name ?: 'Pelanggan tanpa profil') }}</h2>
                            <p>{{ $review->customer?->phone ?: 'Nomor HP tidak dicatat' }}</p>
                        </div>
                        <span class="reservation-status {{ $review->is_published ? 'reservation-status-seated' : 'reservation-status-confirmed' }}">
                            {{ $review->is_published ? 'Dipublikasikan' : 'Menunggu moderasi' }}
                        </span>
                    </div>
                    <p class="review-admin-rating">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }} <span>{{ $review->rating }} / 5</span></p>
                    <p class="review-admin-comment">{{ $review->comment ?: 'Pelanggan mengirimkan nilai tanpa komentar.' }}</p>
                    <form method="POST" action="{{ route('admin.reviews.update', $review) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="is_published" value="{{ $review->is_published ? 0 : 1 }}">
                        <button class="{{ $review->is_published ? 'catalog-delete-button' : 'table-toggle' }}" type="submit">
                            {{ $review->is_published ? 'Sembunyikan ulasan' : 'Publikasikan ulasan' }}
                        </button>
                    </form>
                </article>
            @empty
                <div class="orders-empty"><h2>Belum ada ulasan.</h2><p>Ulasan pelanggan akan muncul di sini untuk dimoderasi.</p></div>
            @endforelse
        </section>

        @if ($reviews->hasPages())
            <nav class="order-pagination" aria-label="Navigasi halaman ulasan">
                {{ $reviews->links() }}
            </nav>
        @endif
    </main>
</body>
</html>
