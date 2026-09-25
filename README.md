# Website PT Ekasa Cold Chain

Website PT Ekasa Cold Chain: Laravel 13 dengan halaman publik Blade + Alpine.js (tanpa Node di
server, tanpa SSR) dan panel admin Filament 5 di `/admin`. Semua teks, tautan, dan gambar
dirender server-side sehingga terbaca oleh mesin pencari dan crawler AI.

Laporan lengkap tahap 1-2: [docs/laporan-tahap-1-2.md](docs/laporan-tahap-1-2.md).

## Kebutuhan

- PHP 8.3+ (`pdo_sqlite`/`pdo_mysql`, `intl`, `mbstring`, `fileinfo`, `exif`, `zip`)
- GD atau Imagick untuk konversi WebP (wajib di produksi, opsional di lokal)
- Node 20+ hanya di mesin lokal untuk build aset

## Menjalankan di lokal (Windows)

PHP 8.2 milik XAMPP ada di PATH Machine, jadi aktifkan PHP 8.3 di setiap terminal baru.
Skrip ini hanya mengubah sesi terminal tersebut:

```powershell
. .\use-php83.ps1        # PowerShell
source ./use-php83.sh    # Git Bash
```

Skrip menampilkan "GD: aktif" di akhir. Jika `php.ini` PHP 8.3 tidak memuat GD, skrip
memakai `scripts/php-ini/ekasa.ini` (GD + memory_limit 1024M), jadi konversi WebP dan
favicon.ico juga jalan saat `php artisan serve` dijalankan dari terminal itu.

```bash
composer install
npm install
cp .env.example .env         # isi ADMIN_EMAIL, ADMIN_PASSWORD (opsional WHATSAPP_NUMBER)
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm run build                # atau: npm run dev
php artisan serve
```

- Website: http://127.0.0.1:8000
- Admin: http://127.0.0.1:8000/admin

## Pemeriksaan

```bash
vendor/bin/pest                          # tes otomatis
vendor/bin/pint --test                   # gaya kode
vendor/bin/phpstan analyse               # analisis statis
node scripts/browser-check.mjs           # perilaku + screenshot (Chrome headless)
node scripts/contrast-check.mjs          # kontras subteks hero di semua foto
node scripts/hero-overlay-check.mjs      # kontras terburuk overlay hero (foto, gambar terang, putih polos)
```

## Konversi WebP

`IMAGE_CONVERSIONS=auto|on|off` di `.env` (default `auto` = aktif jika GD/Imagick ada).
Tanpa konversi, upload tetap berhasil dan situs memakai file original dengan struktur data
yang sama. Status tampil di halaman admin **Pengaturan Situs**.

## Warna brand dan logo

Palet resmi: #0F6B46 (primary), #38A169 (secondary), #2D3748 (neutral). Token ada di
`resources/css/app.css` (`@theme`); komponen hanya memakai token semantik. Rincian, tabel
kontras, dan pemetaan komponen: [docs/warna-brand.md](docs/warna-brand.md). Tujuh token
bisa diubah di admin (**Pengaturan Situs › Warna**).

Logo dan favicon dari klien (PNG) ada di `database/seeders/images/` (logo warna, logo putih
untuk latar gelap, favicon 512 px); bisa diganti di **Pengaturan Situs › Umum**. Favicon
32/180/192 px dibuat otomatis dari favicon yang diunggah. `public/favicon.ico` (16/32/48 px)
juga dibuat ulang setiap kali favicon PNG/WebP/JPEG atau ICO diunggah (favicon SVG tidak bisa
dikonversi oleh GD; file .ico lama tetap dipakai). Membuat ulang secara manual:
`php artisan site:favicon-ico`. Logo navbar 48 px (navbar 80 px), 44 px setelah scroll (68 px),
40 px di mobile. File SVG logo masih diminta ke
klien, lihat [docs/image-credits.md](docs/image-credits.md).

## Pustaka Ikon

Ikon layanan dan statistik diambil dari **Konten Beranda › Pustaka Ikon**. Isinya ikon
orisinal Ekasa (`resources/icons/ekasa/`, daftar di `manifest.php`, disalin ke database oleh
`IconLibrarySeeder`) ditambah ikon SVG yang diunggah admin. SVG unggahan dibersihkan: hanya
bentuk garis (path, circle, rect, line, polyline, polygon, ellipse) yang disimpan, warnanya
menjadi `currentColor`, dan viewBox harus `0 0 24 24`. Ikon nonaktif tidak muncul di pilihan,
tetapi tetap tampil di konten yang sudah memakainya; ikon yang sedang dipakai tidak bisa
dinonaktifkan atau dihapus. Ikon di daftar `review` pada manifest (batch 2) masuk dalam
keadaan nonaktif sampai disetujui.

Ikon batch 2 digambar oleh `node scripts/draw-icons-batch2.mjs`; lembar pratinjau:
`node scripts/icon-review-sheet.mjs <out.png> "<judul>" key1,key2`.

## Foto seeder

Foto di `database/seeders/images/` sudah diperkecil: JPG kualitas 82, lebar maks. 2400 px,
dan foto hero di bawah 500 kB. File asli disimpan di `database/seeders/images/_original/`
(tidak masuk git). Untuk mengganti foto, taruh file baru di `database/seeders/images/`,
lalu jalankan:

```bash
node scripts/optimize-seed-images.mjs
```

Skrip ini hanya memakai codec WebAssembly (`@jsquash/jpeg`, `@jsquash/resize`, dev dependency),
tanpa library native. File asli dipindah ke `_original/` dan selalu dipakai sebagai sumber,
jadi skrip aman dijalankan ulang.

Upload gambar di admin ditolak jika sisi terpanjangnya lebih dari 4000 px.

## Deploy ke cPanel

Lihat bagian 17 di [laporan tahap 1-2](docs/laporan-tahap-1-2.md). Ringkasnya:
unggah proyek (termasuk `public/build`), arahkan document root ke `public/`, isi `.env`.
Server tidak butuh Node. Cek dulu versi PHP dan ekstensi GD di Terminal cPanel:

```bash
php -v                        # harus 8.3 atau lebih baru
php -m | grep -iE "gd|intl|fileinfo|exif|zip|pdo_mysql"
```

Lalu:

```bash
php artisan migrate --force --seed
php artisan storage:link
php artisan filament:assets
php artisan media-library:regenerate   # buat semua versi WebP dan ukuran favicon
php artisan optimize
```
