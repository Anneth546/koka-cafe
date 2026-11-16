<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Meja KOKA CAFE</title>
    <style>
        :root { color-scheme: light; --walnut: #3b2418; --cream: #f5efe6; --gold: #b99a68; --ink: #29231f; }
        * { box-sizing: border-box; }
        body { color: var(--ink); font-family: Arial, sans-serif; margin: 0; padding: 28px; }
        .sheet-header { align-items: end; border-bottom: 2px solid var(--walnut); display: flex; justify-content: space-between; margin: 0 auto 20px; max-width: 1000px; padding-bottom: 14px; }
        h1 { color: var(--walnut); font: 700 24px Georgia, serif; margin: 0; }
        .sheet-header p { color: #756a61; font-size: 10px; margin: 6px 0 0; }
        .screen-actions { background: var(--walnut); border: 0; color: white; cursor: pointer; font-size: 11px; padding: 11px 16px; }
        .screen-warning { background: #fff3d8; border-left: 3px solid var(--gold); color: #604820; font-size: 11px; line-height: 1.6; margin: 0 auto 20px; max-width: 1000px; padding: 12px 14px; }
        .table-grid { display: grid; gap: 14px; grid-template-columns: repeat(3, minmax(0, 1fr)); margin: 0 auto; max-width: 1000px; }
        .table-card { align-items: center; border: 1px solid #d9cdbd; display: flex; flex-direction: column; min-height: 290px; padding: 15px; text-align: center; break-inside: avoid; }
        .brand { color: var(--walnut); font: 700 15px Georgia, serif; letter-spacing: 1px; }
        .table-name { color: var(--walnut); font: 600 19px Georgia, serif; margin: 9px 0 4px; }
        .scan-copy { color: #756a61; font-size: 9px; margin: 0; }
        .qr-image { display: block; height: 174px; margin: 10px auto 5px; object-fit: contain; width: 174px; }
        .footer-copy { color: #756a61; font-size: 8px; margin-top: auto; }
        @page { margin: 12mm; size: A4; }
        @media (max-width: 700px) {
            body { padding: 14px; }
            .sheet-header { align-items: flex-start; flex-direction: column; gap: 12px; }
            .table-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .table-card { min-height: 245px; padding: 10px; }
            .qr-image { height: min(36vw, 150px); width: min(36vw, 150px); }
        }
        @media print {
            body { padding: 0; }
            .sheet-header { margin-bottom: 12px; }
            .screen-actions, .screen-warning { display: none; }
            .table-grid { gap: 8px; grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .table-card { min-height: 76mm; padding: 8mm 4mm; }
            .qr-image { height: 44mm; width: 44mm; }
        }
    </style>
</head>
<body>
    <header class="sheet-header">
        <div>
            <h1>QR MEJA KOKA CAFE</h1>
            <p>Scan kode meja untuk membuka menu dan menghubungkan pesanan ke meja.</p>
        </div>
        <button class="screen-actions" type="button" onclick="window.print()">Cetak QR meja</button>
    </header>

    @if ($isLocalOnly)
        <aside class="screen-warning" role="note">
            URL saat ini menggunakan <strong>{{ $baseUrl }}</strong>, yang biasanya hanya dikenal di komputer ini.
            Agar ponsel dapat membuka menu, buat ulang QR dengan IP LAN atau domain yang bisa dijangkau dari ponsel.
        </aside>
    @endif

    <main class="table-grid">
        @foreach ($tables as $table)
            <article class="table-card">
                <span class="brand">KOKA CAFE</span>
                <h2 class="table-name">{{ $table->label ?: 'Meja '.$table->number }}</h2>
                <p class="scan-copy">Scan untuk pesan dine-in</p>
                <img class="qr-image" src="{{ $qrCodes[$table->id] }}" alt="QR untuk {{ $table->label ?: 'Meja '.$table->number }}">
                <p class="footer-copy">Pesanan dibayar di kasir</p>
            </article>
        @endforeach
    </main>
</body>
</html>