# Panduan Deploy: PT Ekasa Cold Chain ke cPanel Rumahweb

Alur: **laptop → GitHub → `git pull` di server** lewat Terminal cPanel.

- Stack: Laravel 13, PHP 8.3, Blade + Alpine.js, asset Vite
- User cPanel: `exos7198` (home: `/home/exos7198`)
- Domain: `ekasacoldchain.com` (addon domain), document root awal `/home/exos7198/public_html/ekasacoldchain.com`
- Struktur akhir di server:

```
/home/exos7198/ekasa/                          <- repo (di luar public_html: .env, vendor, dll tidak bisa diakses publik)
/home/exos7198/public_html/ekasacoldchain.com  <- symlink ke /home/exos7198/ekasa/public
```

- Asset Vite **di-build di laptop**; folder `public/build` ikut di-commit (server tidak perlu Node/npm).
- Session, cache, dan antrean memakai `database` / `sync`: tidak butuh Redis atau queue worker.
- Tidak ada scheduler (`routes/console.php` hanya berisi perintah manual), jadi **tidak perlu cron job**.

---

## A. Setup awal (sekali saja)

Semua langkah di bagian ini dijalankan di **Terminal cPanel** (atau SSH), kecuali disebutkan lain.

### A1. Set PHP 8.3 dan ekstensi

1. cPanel → **MultiPHP Manager** → centang `ekasacoldchain.com` → pilih **PHP 8.3** → Apply.
2. cPanel → **Select PHP Version** (atau **MultiPHP INI Editor**) → pastikan ekstensi berikut aktif:

   `ctype`, `curl`, `dom`, `exif`, `fileinfo`, `filter`, `gd`, `hash`, `iconv`, `intl`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `phar`, `session`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, `zip`, `zlib`

   `intl` (dipakai Filament), `zip`, `exif`, dan `gd` (konversi WebP) paling sering tidak aktif secara bawaan.
3. Di MultiPHP INI Editor, sarankan: `memory_limit = 256M`, `upload_max_filesize = 8M`, `post_max_size = 16M` (upload gambar CMS maks. 5 MB).

### A2. Cek PHP dan Composer di terminal

```bash
php -v
/opt/cpanel/ea-php83/root/usr/bin/php -v
composer -V
```

- `php -v` di terminal sering masih versi lama. Yang penting `/opt/cpanel/ea-php83/root/usr/bin/php -v` menampilkan 8.3.x. `deploy.sh` otomatis memakai path ini; jika berbeda, set `PHP_BIN` (lihat bagian B).
- Kalau `composer` tidak ada, pasang `composer.phar` lokal di home:

```bash
cd ~
/opt/cpanel/ea-php83/root/usr/bin/php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
/opt/cpanel/ea-php83/root/usr/bin/php composer-setup.php --install-dir=$HOME --filename=composer.phar
rm composer-setup.php
/opt/cpanel/ea-php83/root/usr/bin/php ~/composer.phar -V
```

`deploy.sh` otomatis memakai `~/composer.phar` bila ada.

### A3. Buat SSH key dan pasang sebagai Deploy Key GitHub

```bash
ssh-keygen -t ed25519 -C "deploy-ekasa@exos7198" -f ~/.ssh/ekasa_deploy -N ""
cat ~/.ssh/ekasa_deploy.pub
```

Salin seluruh isi public key (satu baris `ssh-ed25519 AAAA...`).

Di GitHub: repo → **Settings → Deploy keys → Add deploy key** → beri judul (mis. `cPanel Rumahweb`), tempel key, **jangan** centang "Allow write access" (read-only) → Add key.

Buat config SSH supaya key ini dipakai untuk GitHub:

```bash
cat >> ~/.ssh/config <<'EOF'
Host github.com
    HostName github.com
    User git
    IdentityFile ~/.ssh/ekasa_deploy
    IdentitiesOnly yes
EOF
chmod 700 ~/.ssh && chmod 600 ~/.ssh/config ~/.ssh/ekasa_deploy
```

Tes koneksi (jawab `yes` saat ditanya fingerprint):

```bash
ssh -T git@github.com
```

Berhasil jika muncul: `Hi <user>/<repo>! You've successfully authenticated, but GitHub does not provide shell access.`

> Satu deploy key hanya bisa dipakai di satu repo. Jika nanti ada repo lain di server, buat key dan `Host` alias terpisah.

### A4. Clone repo

```bash
cd ~
git clone git@github.com:<AKUN-GITHUB>/<NAMA-REPO>.git ekasa
cd ekasa
git branch --show-current   # harus: main
```

### A5. Buat database di cPanel

cPanel → **MySQL Databases**:

1. **Create New Database** → nama `ekasa` → hasilnya `exos7198_ekasa`.
2. **MySQL Users → Add New User** → nama `ekasa` → hasilnya `exos7198_ekasa` (maks. 16 karakter total; pakai password kuat, simpan).
3. **Add User To Database** → pilih user dan database tadi → **ALL PRIVILEGES**.

Prefix `exos7198_` **wajib** ada di `DB_DATABASE` dan `DB_USERNAME`.

### A6. Isi `.env`

```bash
cd ~/ekasa
cp .env.example .env
nano .env
```

Yang harus diisi/dicek:

| Variabel | Isi |
|---|---|
| `APP_URL` | `https://ekasacoldchain.com` (tanpa `/` di akhir; menentukan domain kanonik www/non-www) |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | dari langkah A5; `DB_HOST=localhost` |
| `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` | akun admin CMS yang dibuat seeder (pakai password kuat) |
| `WHATSAPP_NUMBER` | nomor awal (opsional, bisa diubah di CMS) |
| `MAIL_*` | opsional; isi bila situs perlu kirim email (pakai akun email cPanel) |

Simpan di nano: `Ctrl+O`, Enter, `Ctrl+X`. Lalu buat kunci aplikasi:

```bash
/opt/cpanel/ea-php83/root/usr/bin/php artisan key:generate
```

Amankan file `.env`:

```bash
chmod 600 .env
```

### A7. Install dependency, migrasi, seeder, storage link

```bash
cd ~/ekasa
PHP=/opt/cpanel/ea-php83/root/usr/bin/php
$PHP ~/composer.phar install --no-dev --optimize-autoloader --no-interaction   # atau: composer install ...
$PHP artisan migrate --force
$PHP artisan db:seed --force      # data awal: admin CMS, pengaturan situs, konten, layanan, dll.
$PHP artisan storage:link
$PHP artisan filament:assets
```

- **Jalankan `db:seed` hanya sekali**, di setup awal. Menjalankannya lagi setelah konten diedit lewat CMS bisa menimpa/menduplikasi data awal.
- Perintah `filament:assets` menyalin CSS/JS panel admin ke `public/` (folder itu di-ignore git, jadi dibuat di server).

### A8. Ganti document root dengan symlink

Pastikan folder domain masih kosong/tidak berisi data penting, lalu:

```bash
rm -rf ~/public_html/ekasacoldchain.com
ln -s ~/ekasa/public ~/public_html/ekasacoldchain.com
ls -ld ~/public_html/ekasacoldchain.com     # harus tampil: -> /home/exos7198/ekasa/public
```

Jika cPanel menolak (folder document root domain harus ada), buat ulang lewat cPanel → **Domains** dan ulangi langkah ini.

### A9. Permission storage dan bootstrap/cache

```bash
cd ~/ekasa
mkdir -p storage/app/public storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

Direktori `~/ekasa` dan `~` sendiri sebaiknya `755` (bukan `777`), agar Apache bisa menelusuri symlink: `chmod 755 ~ ~/ekasa ~/ekasa/public`.

### A10. AutoSSL

cPanel → **SSL/TLS Status** → centang `ekasacoldchain.com` dan `www.ekasacoldchain.com` → **Run AutoSSL**. Tunggu beberapa menit sampai sertifikat aktif. `public/.htaccess` sudah memaksa redirect HTTPS, dan middleware `EnforceCanonicalHost` mengarahkan ke host di `APP_URL`.

### A11. Cache produksi dan tes

```bash
cd ~/ekasa
bash deploy.sh
```

Buka `https://ekasacoldchain.com`, lalu `https://ekasacoldchain.com/admin` dan login dengan `ADMIN_EMAIL` / `ADMIN_PASSWORD`. Cek juga `https://ekasacoldchain.com/up` (health check, harus 200).

### A12. Cron scheduler

Tidak diperlukan: proyek ini tidak mendefinisikan jadwal (`Schedule::`). Jika suatu hari ditambah, tambahkan di cPanel → **Cron Jobs** (setiap menit):

```
* * * * * cd /home/exos7198/ekasa && /opt/cpanel/ea-php83/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

---

## B. Alur update rutin

**Di laptop:**

```bash
npm run build          # bangun asset ke public/build
git add -A
git commit -m "Deskripsi perubahan"
git push origin main
```

`public/build` **harus ikut di-commit** setiap kali CSS/JS/Blade (kelas Tailwind baru) berubah. Jika `git status` menampilkan perubahan di `public/build`, itu normal.

**Di server (Terminal cPanel):**

```bash
cd ~/ekasa && bash deploy.sh
```

`deploy.sh` akan: `git pull --ff-only`, `composer install --no-dev`, `migrate --force`, `storage:link` (jika belum ada), `filament:assets`, `optimize:clear`, lalu `config:cache`, `route:cache`, `view:cache`, dan mengatur permission `storage`/`bootstrap/cache`. Aman dijalankan berulang dan berhenti di error pertama.

Jika path PHP/Composer di server berbeda:

```bash
PHP_BIN=/usr/local/bin/php bash deploy.sh
COMPOSER_BIN="/opt/cpanel/ea-php83/root/usr/bin/php $HOME/composer.phar" bash deploy.sh
```

Catatan:
- Perubahan konten dilakukan lewat **CMS** dan tersimpan di database server. Jangan menimpanya dengan seeder.
- Setelah `deploy.sh`, cache konten depan otomatis dibersihkan (`optimize:clear`).
- Jika `git pull` menolak karena ada perubahan lokal di server (mis. seseorang mengedit file di server), simpan/buang dulu: `git status`, lalu `git stash` atau `git checkout -- <file>`.

---

## C. Troubleshooting

**Error 500 / halaman kosong**
- Lihat log: `tail -n 50 ~/ekasa/storage/logs/laravel-*.log` (log `daily`, nama berisi tanggal).
- Jika log kosong, cek log Apache: cPanel → **Errors** atau `~/logs/`.
- Penyebab umum: `.env` belum ada / `APP_KEY` kosong, versi PHP salah, `vendor/` belum ada, permission `storage`, kredensial DB salah.
- Sementara debugging: set `APP_DEBUG=true` di `.env`, lalu `php artisan config:clear`. **Kembalikan ke `false` setelahnya.**

**403 Forbidden**
- Symlink rusak atau tidak boleh diikuti: cek `ls -ld ~/public_html/ekasacoldchain.com` menunjuk ke `/home/exos7198/ekasa/public`.
- Permission jalur: `chmod 755 ~ ~/ekasa ~/ekasa/public`; file dalam `public` `644`.
- Jika `public/.htaccess` memicu error `Options`, hapus baris `Options -MultiViews -Indexes` (host tertentu melarang `Options`).

**Versi PHP salah** (`Your PHP version does not satisfy...`, atau error syntax pada `vendor/`)
- Cek di web: MultiPHP Manager harus 8.3 untuk domain ini. Cek di terminal: gunakan `/opt/cpanel/ea-php83/root/usr/bin/php`, bukan `php` telanjang.
- Jalankan `PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php bash deploy.sh`.

**Permission / "Permission denied" / gagal tulis log & cache**
- `cd ~/ekasa && chmod -R ug+rwX storage bootstrap/cache`
- Pastikan folder ada: `mkdir -p storage/framework/{cache/data,sessions,views} storage/logs`.

**Asset (CSS/JS) tidak muncul atau tampilan rusak**
- Pastikan `public/build` ada di server dan `public/build/manifest.json` cocok dengan file di `public/build/assets/`. Jika tidak, di laptop jalankan `npm run build`, commit, push, lalu deploy ulang.
- `APP_URL` harus `https://ekasacoldchain.com` (bukan `http://` atau domain lain).
- Panel admin tanpa gaya: jalankan `php artisan filament:assets` (sudah otomatis di `deploy.sh`).
- Kelas Tailwind baru tidak tampil: kamu lupa `npm run build` sebelum commit.

**Gambar upload CMS tidak tampil**
- Pastikan `public/storage` ada: `ls -l ~/ekasa/public/storage` (symlink ke `storage/app/public`). Jika tidak: `php artisan storage:link`.
- Pastikan `MEDIA_DISK=public` di `.env`, lalu `php artisan config:cache`.
- Upload gagal untuk file besar: naikkan `upload_max_filesize` dan `post_max_size` di MultiPHP INI Editor.

**Mixed content (peringatan https di browser)**
- `APP_URL` harus `https://...`. Setelah mengubah `.env`, jalankan `php artisan config:cache`.
- Pastikan AutoSSL aktif dan tidak ada URL `http://` yang ditulis manual di konten CMS.

**Perubahan `.env` tidak berpengaruh**
- Karena `config:cache`, jalankan `php artisan config:clear && php artisan config:cache` (atau `bash deploy.sh`).

**`SQLSTATE ... Access denied` / `Unknown database`**
- Periksa prefix `exos7198_` di `DB_DATABASE` dan `DB_USERNAME`, user sudah ditambahkan ke database dengan ALL PRIVILEGES, dan `DB_HOST=localhost`.

**`git pull` gagal: `Permission denied (publickey)`**
- Ulangi `ssh -T git@github.com`; pastikan deploy key terpasang di repo yang benar dan isi `~/.ssh/config` sesuai bagian A3.

**Gagal `ssh-keygen` / `composer` kehabisan memori**
- Jalankan `php -d memory_limit=-1 ~/composer.phar install --no-dev --optimize-autoloader`. Bila akun membatasi proses, ulangi di jam sepi.
