<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#241711">
    <title>Login Staff | KOKA CAFE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="staff-login-body">
    <main class="staff-login-shell">
        <a class="admin-brand" href="{{ route('customer.home') }}">
            <img class="admin-brand-image" src="{{ asset('images/branding/koka-logo.png') }}" alt="">
            <span><strong>KOKA</strong><small>Kopiku &amp; kamu</small></span>
        </a>
        <section class="staff-login-panel">
            <p class="admin-eyebrow">RUANG STAFF</p>
            <h1>Masuk ke operasional.</h1>
            <p class="login-copy">Gunakan akun admin KOKA CAFE untuk melanjutkan.</p>

            @if ($errors->any())
                <div class="admin-alert" role="alert">{{ $errors->first() }}</div>
            @endif

            <form class="staff-login-form" method="POST" action="{{ route('staff.login.store') }}">
                @csrf
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" autofocus>
                <label for="password">Kata sandi</label>
                <input id="password" name="password" type="password" required autocomplete="current-password">
                <button class="admin-primary-button" type="submit">Masuk <span aria-hidden="true">→</span></button>
            </form>
            <a class="back-link" href="{{ route('customer.home') }}">Kembali ke menu customer</a>
        </section>
        <p class="staff-login-footer">Akses khusus admin KOKA CAFE.</p>
    </main>
</body>
</html>