# KOKA CAFE SMART SHOP

Aplikasi pemesanan dine-in dan pengelolaan operasional KOKA CAFE. Teknologi saat ini: Laravel 13, Blade, Eloquent, Vite, dan MySQL.

## Status Implementasi

Saat ini website menyediakan enam kategori menu, 21 produk seed development, pencarian, keranjang, checkout dine-in, dan dashboard admin. Admin dapat mengelola produk dan kategori, foto/menu favorit/ketersediaan, mengonfirmasi pembayaran dengan metode tunai atau non-tunai, memproses antrean pesanan, mencari dan memfilter pesanan, membuat pesanan walk-in, serta membatalkan pesanan yang belum dibayar dengan alasan. Pengelolaan meja mencakup area, kapasitas, status operasional, aktivasi, dan pembuatan ulang token QR. Pelanggan dapat mengirim reservasi meja melalui `/reservasi`; reservasi masuk ke daftar `/admin/reservations`, dengan validasi kapasitas meja dan bentrok jadwal, lalu admin dapat memperbarui statusnya. Inventaris mendukung pencatatan pemasok dan bahan baku, batas stok, resep per produk, pembelian, stok terbuang, dan stok opname. Stok resep otomatis berkurang saat pembayaran dikonfirmasi; pembayaran ditolak jika stok tidak mencukupi, dan menu otomatis tidak ditawarkan bila bahan resep tidak cukup untuk satu porsi. Data pelanggan dibuat dari nomor HP pesanan, menampilkan riwayat transaksi dan saldo poin, serta dapat diperbarui admin. Pelanggan dapat mengirim ulasan setelah pesanan selesai; ulasan harus dimoderasi admin sebelum ditampilkan di beranda. Laporan penjualan menyediakan rentang tanggal, metode pembayaran, produk terlaris, dan total diskon. Admin dapat membuat voucher nominal atau persentase dengan minimum belanja, batas pemakaian, dan masa berlaku. Pelanggan dapat memakai satu voucher per pesanan dan menukarkan poin dengan nilai 100 poin = Rp10.000; setiap Rp10.000 yang dibayar menghasilkan 1 poin. Poin dan kuota voucher yang dipakai dikembalikan jika pesanan yang belum dibayar dibatalkan. Laporan hanya menghitung pembayaran yang dicatat setelah migrasi pembayaran ini; pesanan lama tanpa waktu pembayaran tidak disertakan. Order dan snapshot harga disimpan ke MySQL; scan token meja aktif mengikat session dan order ke meja. Pengaturan cafe, refund transaksi yang sudah dibayar, shift kasir, ekspor laporan, dan fitur operasional lanjutan masih dalam pengerjaan. Aplikasi belum siap untuk seluruh kebutuhan operasional produksi.

Logo KOKA yang digunakan pada halaman depan dan dashboard admin berada di `public/images/branding/koka-logo.png`.

## Persyaratan

- PHP 8.3 atau lebih baru dengan ekstensi `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, dan `zip`.
- Composer 2.
- Node.js dan NPM.
- MySQL 8.
- Laravel Herd atau server PHP lokal.

Di mesin pengembangan saat ini PHP 8.3 Laragon berada di `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64`. Pastikan Composer berjalan dengan PHP 8.3, bukan PHP 8.0 dari XAMPP. Ekstensi ZIP perlu diaktifkan untuk proses Composer.

## Instalasi Lokal

1. Pastikan PHP 8.3 aktif di PATH, lalu jalankan `composer install`.
2. Salin `.env.example` menjadi `.env` dan sesuaikan kredensial MySQL.
3. Jalankan MySQL melalui Laragon dan buat database `koka_cafe`.
4. Jalankan `php artisan key:generate`.
5. Jalankan `php artisan migrate --seed` untuk membuat tabel awal dan katalog development.
6. Jalankan `php artisan storage:link` agar foto produk yang diunggah dapat ditampilkan.
7. Jalankan `npm install` lalu `npm run build`.
8. Dari folder proyek, jalankan `herd link koka-cafe` lalu buka `http://koka-cafe.test`. Alternatif sementara: `php artisan serve`.

Untuk domain Herd, pastikan Herd memakai PHP 8.3 atau lebih baru. CLI Herd pada mesin ini belum tervalidasi karena PATH masih menunjuk PHP 8.0 dari XAMPP.

Seeder hanya berisi data contoh katalog dan akun admin lokal development, bukan data transaksi produksi. Akun admin demo hanya dibuat saat `APP_ENV=local`:

- Admin: `admin@koka.test` / `KokaLocal2026!`

Tombol **Login staff** di landing page menuju `/staff/login`. Hanya akun admin yang dapat masuk; admin mengelola seluruh alur pesanan dan meja/QR yang tersedia. Pelanggan tidak perlu membuat akun untuk memilih menu, checkout, atau mengirim reservasi. Ganti kredensial demo sebelum menaruh aplikasi pada jaringan yang dapat diakses pihak lain.

## Alur Pesan Customer

- Buat lembar QR untuk seluruh meja aktif: `php artisan koka:qr-tables --base-url=http://ALAMAT-YANG-BISA-DIAKSES-PONSEL:8000`.
- Buka `storage/app/private/qr-meja.html`, lalu gunakan tombol cetak di halaman. File berisi satu QR unik per meja dan tidak dipublikasikan sebagai route web.
- Admin dapat membuka `/admin/tables` setelah login, mengaktifkan atau menonaktifkan meja, lalu mencetak QR. Tombol cetak memakai alamat yang sedang dibuka atau alamat lain yang dimasukkan pada form.
- Untuk uji satu Wi-Fi, jalankan server agar menerima koneksi LAN, lalu pakai IP Wi-Fi komputer sebagai `--base-url`. Ponsel dan komputer harus berada di jaringan yang sama; Windows Firewall perlu mengizinkan koneksi. IP dinamis mungkin berubah, jadi buat ulang QR bila alamat berubah.
- Scan QR meja untuk membuka `/meja/{token}`; token acak dan meja aktif mengikat session ke meja.
- Pelanggan tetap sebagai guest: tidak ada login customer yang diwajibkan untuk memesan.
- Reservasi meja dapat dikirim melalui tautan **Reservasi meja** atau halaman `/reservasi`. Nama, nomor HP, meja, jumlah tamu, waktu, dan catatan tersimpan dan dapat dikelola admin di `/admin/reservations`.
- Tambahkan produk dari halaman menu, atur jumlah di `/keranjang`, isi nama/catatan opsional, kode voucher (jika ada), dan nomor HP bila ingin menukar poin, lalu kirim pesanan.
- Harga dan total dihitung ulang di server dari harga produk database. Request checkout tanpa meja aktif atau dengan produk nonaktif ditolak.
- Pesanan masuk dengan status `Menunggu pembayaran`; admin dapat mengonfirmasi metode pembayaran dari halaman pesanan. Halaman status menampilkan nomor order, meja, isi, diskon, dan total.
- Penukaran poin menggunakan kelipatan 100 poin (Rp10.000); saldo poin bertambah saat pembayaran dikonfirmasi. Voucher dan poin dapat digunakan bersamaan.
- Retry checkout menggunakan kunci idempotensi agar tidak membuat order kedua.

## Pengujian

```powershell
php artisan test
```

Tes otomatis menggunakan SQLite in-memory. `.env.example` menyiapkan MySQL untuk aplikasi lokal.

## Struktur Saat Ini

- `app/Http/Controllers/CustomerHomeController.php`: katalog dan pencarian customer.
- `app/Http/Controllers/CustomerReservationController.php`: form dan pengiriman reservasi meja customer.
- `app/Services/ReservationService.php`: validasi kapasitas, jadwal bentrok, dan penyimpanan reservasi.
- `app/Http/Controllers/CartController.php`: keranjang session.
- `app/Http/Controllers/DiningTableController.php`: validasi token meja.
- `app/Http/Controllers/CheckoutController.php`: checkout transaksional dan status order.
- `app/Services/OrderPricingService.php`: perhitungan diskon, penukaran poin, dan kuota voucher.
- `app/Http/Controllers/Admin/PromotionController.php`: pengelolaan voucher.
- `app/Http/Controllers/StaffAuthController.php`: autentikasi staff.
- `app/Http/Controllers/Admin/DiningTableController.php`: daftar meja, status aktif, dan lembar QR terlindungi.
- `app/Http/Middleware/EnsureStaffRole.php`: batas akses role.
- `app/Console/Commands/GenerateDiningTableQRCodes.php`: generator QR SVG dan lembar cetak privat.
- `app/Models`: kategori, produk, meja, order, pelanggan, voucher, ulasan, dan snapshot item order.
- `database/migrations`: skema kategori, produk, meja, order, dan item order.
- `database/seeders`: 21 menu development dan 12 meja bertoken acak.
- `resources/views/customer`: halaman katalog, reservasi, keranjang, dan status pesanan.
- `resources/views/admin/tables`: pengelolaan meja/QR.
- `resources/views/auth/staff-login.blade.php`: login admin.
- `resources/views/print/dining-tables.blade.php`: lembar QR meja siap cetak.
- `public/css/storefront.css`: gaya responsif storefront.

## Keamanan

- Jangan commit `.env` atau kredensial database.
- Gunakan kredensial berbeda dari nilai default untuk lingkungan selain development.
- QRIS belum terhubung ke payment gateway. Pembayaran tidak boleh dianggap berhasil otomatis.
- Sebelum produksi, siapkan HTTPS, backup database MySQL terjadwal, dan akun staff dengan password baru.

Progres dan langkah lanjutan dicatat di [PROJECT_PROGRESS.md](PROJECT_PROGRESS.md).