# SECURITY_AUDIT.md: PT Ekasa Cold Chain

- **Audit awal (Tahap 1):** baseline commit `0d073f0`. **Perbaikan (Tahap 2):** 8 commit per kategori, di bawah.
- **Acuan:** OWASP Top 10 2025 dan OWASP ASVS Level 2.
- **Status:** semua temuan yang bisa ditutup dengan kode sudah ditutup dan diuji. Sisanya adalah tindakan manual di hosting/Cloudflare (lihat `SECURITY_CHECKLIST_DEPLOY.md`) dan beberapa batasan yang disengaja (bagian 5).
- Dokumen terkait: `DEPLOY.md` (langkah server dan variabel `.env` baru), `SECURITY_CHECKLIST_DEPLOY.md` (hal manual), `docs/kesiapan-produksi.md` (retensi data pribadi).

## 1. Hasil

| Tingkat awal | Jumlah | Hasil |
|---|---|---|
| Critical | 0 | - |
| High | 1 (F-1) | **diturunkan** setelah diuji (bagian 2), tetap diperbaiki sebagai pertahanan berlapis |
| Medium | 12 | semua diperbaiki; G-1 (CSP) berjalan **report-only** sampai Anda menyalakan `enforce` |
| Low | 10 | 9 diperbaiki, 1 sebagian (K-4), 1 hanya dicatat (I-2: `sail`/`pail`) |

| Commit | Kategori | Temuan |
|---|---|---|
| `421ee25` | B. XSS | B-1, B-2, B-3 |
| `020f7b2` | F. Upload | F-1, F-2, F-3, F-4 |
| `edefb6b` | G. Header, HTTPS, proxy, server | G-1, G-2, G-3, G-4, G-5 |
| `05303a9` | D. Akses | D-1 (OK-9, OK-10 dipertahankan) |
| `8f5bfb1` | C. Autentikasi | C-1, C-2, C-5, C-6 (C-8 = item lanjutan) |
| `78b241d` | J. Error dan log | J-1, J-2, H-2 (sebagian) |
| `dee7d4a` | K + H. Validasi, privasi | K-1, K-4 (sebagian), H-3 |
| `221b2db` | E + I. Session, dependency | E-2, I-2 (sebagian) |

Verifikasi: 69 tes keamanan baru (`SecurityXssTest`, `UploadSecurityTest`, `SecurityHeadersTest`, `AccessControlTest`, `AuthSecurityTest`, `ErrorPagesAndLoggingTest`, `LeadRetentionTest`, `SessionAndConfigSecurityTest`), Pint dan PHPStan bersih, `composer audit` dan `npm audit` 0 kerentanan, pemeriksaan CSP di browser pada 7 halaman (0 pelanggaran), widget Turnstile diuji di browser dengan kunci uji Cloudflare.

## 2. Koreksi atas audit Tahap 1

Dua pernyataan saya di Tahap 1 ternyata terlalu keras atau keliru; ini hasil pengujian di Tahap 2:

1. **F-1 (High) dinilai terlalu tinggi.** Saya menulis bahwa file upload memakai nama asli dan bahwa `shell.php` berisi PNG akan tersimpan sebagai `.php`. Setelah diuji: (a) Filament sudah menamai upload `ULID.<ekstensi>`, bukan nama asli; (b) Livewire menentukan tipe dari ekstensi nama, sehingga `evil.php`, `evil.phtml`, `EVIL.PHAR`, dan `photo.png.php` **sudah ditolak validasi** sebelum disimpan. Jalur ke eksekusi kode jadi jauh lebih sempit: praktis butuh ekstensi yang dianggap gambar oleh validator tetapi dijalankan sebagai PHP oleh server, dan itu tidak ada. Tingkat yang tepat: **Low-Medium**. Perbaikannya tetap dikerjakan karena murah dan berlapis (bagian 3, F-1).
2. **"Nama asli dipertahankan"** hanya berlaku untuk file yang ditambahkan lewat seeder/kode, bukan lewat form admin.

Hal lain yang berubah dari rencana:

- `env()` di `bootstrap/app.php` **tidak terbaca** setelah `config:cache` (saat itu `.env` tidak dibaca). Karena itu TrustProxies dan HTTPS paksa dibaca lewat `config()` di service provider, bukan di `bootstrap/app.php`.
- Untuk B-2 tidak perlu menulis ulang email: Laravel 13 punya `Markdown::withSecuredEncoding()`, dan itu yang dipakai.
- Alt foto About memakai seluruh HTML cerita sebagai teks alt (bug aksesibilitas yang ketahuan di tes B-1); sekarang teks polos maksimal 125 karakter.
- `pint --test` di `HEAD` sebelumnya gagal pada `PostForm.php` (hanya gaya); ikut dirapikan di commit B.

## 3. Status per temuan

Semua status di bawah: **selesai** kecuali disebut lain.

### A. Injection
Tidak ada temuan (tidak ada SQL mentah, eksekusi perintah, atau path dari input; OK-1 sampai OK-3).

### B. XSS
- **B-1** Rich text CMS dirender tanpa sanitasi (`blog/show.blade.php:59`, `sections/about.blade.php:97`). → `App\Support\Html::rich()` (sanitizer bawaan Filament, Symfony HtmlSanitizer) dipakai di `Post::toDetail()` dan `AboutContent::toFrontend()`. Tanpa paket baru; `mews/purifier` tidak dipasang. Tes: skrip, `onerror`, `javascript:`, iframe terbuang.
- **B-2** Markdown di email lead menjadi tautan/gambar aktif. → `Markdown::withSecuredEncoding()` (AppServiceProvider) + tanda `|` pada nilai tabel diganti. Tes: tidak ada `<a>`/`<img>` ke URL pengunjung.
- **B-3** URL Instagram/LinkedIn menerima `ftp://`. → aturan `url:http,https`. Tes: `ftp:`, `javascript:`, `data:` ditolak.

### C. Autentikasi
- **C-1** Rate limit login hanya per IP. → `LoginThrottle`: 5 gagal/15 menit per email+IP, 10 gagal/30 menit per email lintas IP, 30 gagal/15 menit per IP; hanya percobaan gagal yang dihitung; pesan generik yang sama untuk email tak dikenal, password salah, dan akun tanpa akses; berhasil login menghapus hitungan akun+IP.
- **C-2** Tidak ada cara mengganti password, seeder menimpa password. → `->profile()` di panel; `php artisan admin:create [--reset-password]` (input tersembunyi); `AdminUserSeeder` tidak jalan di production, tidak mengubah akun yang ada, menolak password lemah. Aturan password: min 12, huruf besar/kecil, angka, simbol, `uncompromised` di production (lebih ketat dari `min(8)` di brief). `ADMIN_*` dihapus dari `.env.production.example`.
- **C-5** Turnstile. → opsional di form Kontak dan login admin; `TURNSTILE_ENABLED=false` default; lolos di `APP_ENV=local`; **fail-open** (dicatat di log keamanan) bila Cloudflare tidak terjangkau/error atau secret key salah. Batasan: Turnstile butuh JavaScript (form Kontak tanpa JS tidak bisa dipakai saat aktif).
- **C-6** Path admin tetap dan diumumkan di robots.txt. → `ADMIN_PATH`; path kustom tidak ditulis di robots.txt; halaman admin dikirim dengan `X-Robots-Tag: noindex`.
- **C-8** 2FA: **item lanjutan** (sesuai brief), langkah aktivasi ada di `SECURITY_CHECKLIST_DEPLOY.md`; paket TOTP sudah terpasang sebagai dependensi Filament.

### D. Akses
- **D-1** Semua akun adalah admin. → `users.is_admin` (default false; migrasi menandai akun yang sudah ada sebagai admin), `User::canAccessPanel()` mewajibkannya, `is_admin` tidak mass-assignable, `ContactMessagePolicy` untuk data pribadi leads. Catatan jujur: resource lain (layanan, blog, dst.) hanya dilindungi oleh gerbang panel (hanya admin yang masuk); policy per model tidak ditambahkan karena hanya ada satu peran.

### E. Session
- **E-2** → `SESSION_LIFETIME=60`, `HttpOnly` dan `SameSite=Lax` eksplisit, dan `session.secure` **mengikuti `FORCE_HTTPS`** (kecuali `SESSION_SECURE_COOKIE` diisi). Temuan sampingan: di template lama `SESSION_SECURE_COOKIE=true` padahal SSL belum ada; cookie itu tidak akan pernah tersimpan lewat http, sehingga login dan form gagal (419) di deploy pertama.

### F. Upload
- **F-1** → (Low-Medium setelah uji). `ImageUpload::secure()` menggantikan langkah simpan Filament: isi di-decode dan di-encode ulang oleh GD (`SafeImage`), nama `ULID.<ekstensi dari tipe terdeteksi>`; `storage/app/public/.htaccess` (ter-track di git) mematikan handler skrip, menolak nama skrip, dan hanya menyajikan berkas gambar; `deploy.sh` berhenti bila file itu hilang. Tes: PHP di data trailing dan di chunk metadata PNG terbuang.
- **F-2** SVG tidak disanitasi. → SVG **dihapus** dari logo, favicon, logo klien, dan foto truk (PNG/WebP/JPEG; favicon juga ICO). Enam logo klien seed dirender ke PNG.
- **F-3** Metadata EXIF tetap ada. → ikut terbuang oleh re-encode (rotasi EXIF diterapkan dulu), sisi terpanjang dibatasi 4000 px. Format asli dipertahankan (tidak diubah menjadi WebP: varian WebP untuk performa sudah dibuat oleh conversion yang ada).
- **F-4** Batas upload: validasi per field (1–5 MB, dimensi) tetap jadi penentu; batas upload sementara Livewire (12 MB) tidak diubah. ClamAV: tidak diimplementasikan (sesuai brief), ada di checklist.

### G. Header, HTTPS, server
- **G-1** → `SecurityHeaders`: `X-Frame-Options`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`, `COOP`, hapus `X-Powered-By`; HSTS via `HSTS_MAX_AGE` (default 0, contoh produksi 300, tanpa preload); CSP halaman publik (bukan admin) dengan nonce, `CSP_MODE=report-only|enforce|off`. `'unsafe-eval'` dipertahankan sesuai keputusan (Alpine standar). Diperiksa di browser: 0 pelanggaran di 7 halaman. **Default-nya report-only**; Anda yang menyalakan `enforce` setelah memeriksa konsol (checklist bagian 2).
- **G-2** Redirect HTTPS tak bersyarat. → dihapus dari `.htaccess`; `FORCE_HTTPS=false` default; `EnforceCanonicalHost` dan `URL::forceScheme` hanya memaksa https bila dinyalakan setelah SSL aktif.
- **G-3** TrustProxies belum ada. → `TRUSTED_PROXIES` (kosong = tidak ada; `cloudflare`, `*`, atau daftar IP/CIDR), daftar IP Cloudflare bawaan + `php artisan security:cloudflare-ips`. Tes: `X-Forwarded-For` hanya dipercaya dari proxy tepercaya.
- **G-4** → `.htaccess` menolak dotfile (kecuali `.well-known`), `composer.*`, `package*.json`, `artisan`, log, dump, backup.
- **G-5** CORS → `config/cors.php` eksplisit tanpa path (tidak ada API publik).

### H. Data sensitif dan privasi
- **H-2** Log memuat alamat penerima dan teks error SMTP. → diganti entri `security` berisi id lead dan nama kelas exception saja.
- **H-3** IP/user-agent tanpa retensi. → `php artisan leads:prune` terjadwal harian lewat `withSchedule` (`bootstrap/app.php`): 60 hari IP/user-agent dikosongkan, 12 bulan pesan dihapus; `--dry-run`; diatur lewat `.env`. Butuh cron `schedule:run` di server. `docs/kesiapan-produksi.md` dilengkapi.

### I. Dependency
- **I-2** → `laravel/tinker` (beserta `psy/psysh`, `nikic/php-parser`) dipindah ke `require-dev`; hanya tiga paket yang pindah bagian, tidak ada perubahan versi. `laravel/sail` dan `laravel/pail` (dev) **tidak dihapus** (hanya dicatat); boleh dihapus bila Anda setuju. `composer audit` dan `npm audit`: 0 kerentanan.

### J. Error dan log
- **J-1** → halaman 403/404/419/429/500/503 sesuai desain, dwibahasa mengikuti URL, mandiri (tanpa database, Vite, atau skrip), tanpa detail exception.
- **J-2** → channel `security` (daily, info, 90 hari): login sukses/gagal/diblokir/lockout, logout, perubahan akun (nama kolom saja), lead dihapus (id), upload dan hapus upload admin, email lead gagal, honeypot/Turnstile ditolak, Turnstile fail-open, pembersihan lead. Tanpa password, token, isi pesan, atau email lengkap (email dimasking). `dontFlash` diperluas.

### K. Validasi
- **K-1** → `StoreContactMessageRequest`; honeypot dan Turnstile dipindah ke middleware `GuardContactForm` yang berjalan sebelum validasi (bot tetap tidak tahu aturan mana yang dilanggar). Perilaku tidak berubah.
- **K-4** Banjir email dari bot terdistribusi. → **sebagian**: Turnstile tersedia (mati default). Limiter global tambahan **tidak** dibuat; pertahanan sekarang honeypot + 5 kiriman/15 menit/IP (IP asli setelah `TRUSTED_PROXIES`).

## 4. Yang sudah baik sejak awal (tidak berubah)

Tidak ada SQL mentah/eksekusi perintah/path dari input; tidak ada `x-html`; data ke Alpine hanya angka ter-cast dan `@js`; JSON-LD dengan `JSON_HEX_TAG`; `regenerate()` saat login dan `invalidate()` + `regenerateToken()` saat logout; CSRF tanpa pengecualian; semua model `#[Fillable]`; registrasi/reset password tidak aktif; tidak ada password default di kode; `.env` tidak pernah ter-commit; redirect tidak dari input; sitemap/robots memakai `APP_URL`; rute `/dev/*` hanya di `local`; ikon SVG library disanitasi dengan daftar putih. Rinciannya di versi Tahap 1 (riwayat git dokumen ini).

## 5. Risiko sisa dan batasan (jujur)

1. **CSP memakai `'unsafe-eval'` dan `style-src 'unsafe-inline'`** (Alpine standar dan atribut `style`). Risiko rendah karena tidak ada data pengguna di atribut `x-*` dan `<script>` tetap wajib bernonce, tetapi ini bukan CSP paling ketat. Jalur pengetatan: `@alpinejs/csp` (refactor besar, tidak dikerjakan).
2. **CSP default report-only**: belum memblokir apa pun sampai Anda menyalakan `enforce`.
3. **Tanpa 2FA** pada panel admin (item lanjutan). Kompensasi: lockout login, password 12+ karakter, `admin:create`, log keamanan.
4. **Turnstile mati default**, dan saat aktif form Kontak butuh JavaScript.
5. **Belum ada uji di server nyata**: `.htaccess` folder upload, aturan `FilesMatch`, dan perilaku Apache/LiteSpeed di Rumahweb hanya diuji lewat tes aplikasi dan pembacaan; uji di server ada di checklist bagian 7. `deploy.sh` juga belum pernah dijalankan sungguhan (peringatan di `DEPLOY.md`).
6. **Daftar IP Cloudflare bawaan** bisa usang; `security:cloudflare-ips` menyegarkannya (jadwalkan bulanan).
7. **Policy per model hanya untuk leads**; selebihnya mengandalkan gerbang panel satu peran.
8. **Data pribadi di backup** tidak ikut dibersihkan `leads:prune`; masa simpan backup harus Anda tetapkan.
9. **Kolom "Cari" bawaan Filament** dan komponen inti Filament lain tidak diaudit kontrasnya selain halaman Pesan Masuk (kolom Cari sudah diperbaiki dengan CSS kecil).

## 6. Temuan baru selama Tahap 2 (sudah ditangani)

| Temuan | Penanganan |
|---|---|
| Logo klien seed berupa SVG, padahal SVG dihapus | dirender ke PNG, SVG dihapus dari repo |
| `SESSION_SECURE_COOKIE=true` di template produksi memutus login di deploy pertama | mengikuti `FORCE_HTTPS` |
| Alt foto About berisi HTML cerita | teks polos dipotong 125 karakter |
| `Http::fake` kedua tidak menimpa stub pertama (hanya tes) | tes memakai `Http::sequence()` |
| Tes lama `HomeServicesTest` tergantung daftar layanan di halaman penuh | dibatasi ke section services |

## 7. Tindakan manual

Semuanya di `SECURITY_CHECKLIST_DEPLOY.md`: isi `.env` produksi, AutoSSL lalu `FORCE_HTTPS`, `expose_php Off`, izin berkas, password database, deploy key read-only, backup ke luar server, 2FA (cPanel/Clientzone/GitHub), SPF/DKIM, antivirus hosting, cron `schedule:run`, Turnstile, dan aktivasi Cloudflare.
