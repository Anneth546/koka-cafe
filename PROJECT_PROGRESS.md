# Progres Implementasi

## Tahap 1-4: Bootstrap, Katalog, QR, dan Admin Meja

Status: fondasi katalog selesai; aplikasi keseluruhan belum siap produksi.

- Workspace awal kosong. Laravel 13.40.0 dipasang memakai PHP 8.3.30 dari Laragon.
- `php_zip.dll` harus diaktifkan saat Composer dijalankan pada instalasi PHP lokal ini. PHP 8.0 yang lebih awal di PATH tidak kompatibel dengan Laravel 13.
- Halaman `/` dan `/menu` membaca produk aktif melalui Eloquent; pencarian `?q=` dan filter `?kategori=` tersedia.
- Navigasi menampilkan beranda, Login staff, menu, favorit, enam kategori, cerita KOKA, serta hitungan keranjang. Login hanya untuk owner/admin; customer tetap guest.
- Seeder idempotent menyediakan 6 kategori, 21 produk development, dan 12 meja dengan token acak.
- Keranjang mendukung tambah, ubah jumlah, hapus, serta ringkasan berdasarkan harga database.
- `/meja/{token}` hanya menerima token acak 48 karakter yang valid dan meja aktif; meja disimpan di session.
- Checkout memerlukan meja aktif, memakai ulang kunci idempotensi, menghitung ulang harga di server, dan menyimpan order serta snapshot nama/harga item dalam satu transaksi.
- `/pesanan/{token}` menampilkan status awal Menunggu pembayaran dan ringkasan order.
- `php artisan koka:qr-tables --base-url=...` membuat lembar cetak privat dengan QR SVG unik untuk setiap meja aktif; meja nonaktif dilewati.
- Lembar `storage/app/private/qr-meja.html` berhasil dibuat untuk 12 meja memakai IP Wi-Fi `172.20.10.3`; server development saat ini mendengarkan pada port 8000 di jaringan lokal.
- Host `.test`/localhost menampilkan peringatan, karena umumnya tidak dapat dibuka dari ponsel. Gunakan IP/domain yang dapat dijangkau dari jaringan ponsel dan regenerasi QR bila IP berubah.
- Login staff owner/admin tersedia di `/staff/login`; customer dan role tanpa halaman operasional ditolak.
- `/admin/tables` hanya dapat diakses owner/admin; halaman menampilkan meja, status, dan aksi aktif/nonaktif.
- `/admin/tables/qr` menghasilkan lembar QR privat untuk admin. Form memakai host yang sedang diakses sebagai default dan mengizinkan URL alternatif.
- `StaffUserSeeder` hanya membuat akun owner/admin demo saat `APP_ENV=local`.
- Satu alur browser nyata dari tambah produk, scan token meja, checkout, sampai halaman status MySQL berhasil; order uji dibersihkan kembali.
- Autentikasi dan admin meja selesai untuk owner/admin. Belum ada tabel table-session terpisah, dashboard kasir/barista, pembayaran, inventaris, analitik, rekomendasi, atau loyalitas.
- UI responsif berbahasa Indonesia dibuat. Logo referensi belum tersedia; placeholder ditampilkan secara eksplisit.

## Validasi

- `php artisan --version`: Laravel Framework 13.40.0.
- `php artisan test`: 23 tes lulus, 90 assertion.
- Tes end-to-end guest membuktikan scan token meja, tambah produk, checkout, simpan order, halaman status, dan keranjang kosong tanpa autentikasi customer.
- `TestingDatabaseIsolationTest` memastikan Laravel test bootstrap memakai SQLite in-memory.
- MySQL row count diverifikasi setelah suite: 6 kategori, 21 produk, 12 meja; order uji dihapus setelah verifikasi checkout nyata.
- `chillerlan/php-qrcode` v6.0.1 terpasang dan tes generator memastikan meja aktif menghasilkan jumlah QR yang sesuai, meja nonaktif dilewati, serta URL invalid ditolak.
- Lembar `storage/app/private/qr-meja.html` aktual memuat 12 QR dan semuanya ter-render; file dibuat dengan base URL LAN `http://172.20.10.3:8000`.
- Migration awal dan order berhasil dijalankan di MySQL 8.4.3.
- Seeder katalog dan meja berhasil; database `koka_cafe` berisi 6 kategori, 21 produk, dan 12 meja.
- Seeder staff lokal menyediakan owner/admin demo; akun kasir/barista belum dibuat karena dashboardnya belum tersedia.
- Composer dependency install berhasil setelah ekstensi ZIP diaktifkan.
- `APP_KEY` lokal dibuat dengan `php artisan key:generate`.
- `npm.cmd install`: 55 paket ditambahkan, 0 kerentanan dilaporkan.
- `npm.cmd run build`: build Vite berhasil.
- Browser: halaman termuat di desktop dan lebar 390px tanpa overflow horizontal.

## Kondisi Lingkungan

- PHP aktif global: 8.0.30 dari XAMPP; gunakan PHP 8.3.30 Laragon untuk proyek ini.
- Composer: 2.9.4.
- Node.js: 24.13.0; NPM: 11.6.2.
- Herd terpasang, tetapi CLI gagal saat memakai PHP aktif global. Domain Herd belum tervalidasi.
- MySQL Laragon 8.4.3 berjalan di port 3306 dan menerima koneksi TCP root lokal tanpa password.
- `.env.example` dan `.env` lokal memakai MySQL; `tests/TestCase.php` memaksa SQLite in-memory sebelum aplikasi Laravel boot, agar task environment MySQL tidak pernah disentuh tes.
- Server pengembangan berjalan pada `http://172.20.10.3:8000` dengan bind jaringan lokal; ponsel perlu berada di Wi-Fi yang sama.
- Aset logo `public/images/branding/koka-logo-reference.jpg` belum tersedia.

## Langkah Berikutnya

1. Implementasikan dashboard kasir dan pembayaran Cash/QRIS terverifikasi.
2. Implementasikan antrean dan dashboard barista yang hanya menerima order telah dibayar.
3. Implementasikan akun customer, profil, riwayat, dan loyalty.
4. Tambahkan inventaris, resep, stock movement FEFO, alert, dan restock.
5. Tambahkan analitik, rekomendasi, laporan, serta pengujian operasional terkait.