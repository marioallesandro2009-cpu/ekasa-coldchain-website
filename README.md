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
```

## Konversi WebP

`IMAGE_CONVERSIONS=auto|on|off` di `.env` (default `auto` = aktif jika GD/Imagick ada).
Tanpa konversi, upload tetap berhasil dan situs memakai file original dengan struktur data
yang sama. Status tampil di halaman admin **Pengaturan Situs**.

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
unggah proyek (termasuk `public/build`), arahkan document root ke `public/`, isi `.env`, lalu

```bash
php artisan migrate --force --seed
php artisan storage:link
php artisan filament:assets
php artisan media-library:regenerate   # buat semua versi WebP
php artisan optimize
```
