# Audit akhir tahap 1-2

Tanggal: 25 September 2026. Diperiksa dari repository baru (satu commit awal) dan build di
`public/build`. Screenshot: `docs/screenshots/audit-akhir/`.

**Ringkasan:** 27 LULUS, 0 GAGAL, 3 DIPERBAIKI, 1 PERLU KEPUTUSAN (dari 31 pemeriksaan).

| No | Pemeriksaan | Status | Catatan |
|---|---|---|---|
| **A** | **Kode & build** | | |
| 1 | Dependency sesuai stack, tanpa sisa | DIPERBAIKI | Tidak ada inertia, vue, react, gsap, motion, three, fortify di composer/npm. `composer.json` masih bernama `laravel/vue-starter-kit` (sisa starter kit): diganti `ekasa/website` + deskripsi, hash lock diperbarui. |
| 2 | migrate:fresh --seed, build, Pest, Pint, PHPStan | LULUS | Seed bersih; `npm run build` tanpa warning; 75 tes lulus, 1 di-skip (upload dengan GD, GD diblokir di lokal); Pint dan PHPStan 0 error. |
| 3 | Tanpa console.log/dd/dump, kode mati, route /dev di production | LULUS | Tidak ada panggilan debug di app/resources/routes (console.log hanya output skrip CLI di `scripts/`). Semua kelas `app/Support` dipakai. `/dev/icons` hanya didaftarkan saat `APP_ENV=local` (ada tesnya). |
| 4 | .env.example lengkap, tanpa nilai pihak lain | LULUS | `APP_NAME="PT Ekasa Cold Chain"`, `ADMIN_EMAIL=admin@example.com`, WhatsApp, konversi gambar, dan driver terjemahan terdokumentasi. |
| **B** | **Identitas & hak cipta** | | |
| 5 | Tidak ada "coldspace" di kode, publik, dan riwayat git | LULUS | Repository dimulai ulang (1 commit). Kemunculan tersisa hanya pengecualian yang diizinkan: "referensi desain" di `docs/` dan tes yang memastikan nama itu tidak muncul di halaman publik. `package-lock.json` (nama dari nama folder) diganti `ekasa-website`. Riwayat lama disimpan di `../coldspace-git-backup/`. |
| 6 | Tanpa logo, favicon, tagline, screenshot pihak lain; badge orisinal | PERLU KEPUTUSAN | Logo/favicon placeholder milik proyek, badge "Temperature-Controlled Logistics", screenshot lama dihapus. Namun headline "Keep your products fresh with" dan nama/deskripsi layanan (misalnya "Reefer & dry fleet for rent", "Connected to major marketplaces") berasal dari brief awal dan mungkin mengikuti teks situs referensi. Perlu dikonfirmasi atau ditulis ulang. |
| 7 | Nama, kontak, domain hanya dari Settings/config | LULUS | Tes mengganti nama/kontak/domain di Settings dan memastikan halaman publik, llms.txt, sitemap, robots.txt mengikutinya. |
| 8 | image-credits.md dan credits.md | DIPERBAIKI | `docs/credits.md` belum ada: dibuat (ikon orisinal, Lucide ISC, font OFL, placeholder, paket MIT). Kelima foto berstatus **PERLU DICEK** (metadata kosong). |
| **C** | **Admin** | | |
| 9 | Login dari .env; tab Settings tersimpan dan tampil | LULUS | Umum, Hero, Mega Menu, Teks & Label, Warna, SEO, Terjemahan diuji lewat Livewire; tes baru untuk tab Hero, Mega Menu, dan glosarium Terjemahan. |
| 10 | Resource Layanan, Slide Hero, Statistik Hero, Logo Klien | LULUS | CRUD, toggle aktif, urutan drag and drop, EN/ID berdampingan, badge Versi ID. Logo Klien sengaja satu bahasa (nama klien = nama merek), tanpa badge. |
| 11 | Validasi upload; upload tanpa GD | LULUS | Format, 5 MB, sisi terpanjang 4000 px diuji; upload dengan konversi `off` lulus. |
| 12 | Cache terhapus untuk kedua bahasa | LULUS | Ada tesnya. |
| 13 | Tidak ada field admin usang | LULUS | Chevron dan tracker sudah dihapus (migrasi); semua kunci Teks & Label dipakai di view. Section Insights/Careers dihapus. |
| **D** | **Halaman publik** | | |
| 14 | View-source: navbar, hero, meta, canonical, hreflang, og:locale, JSON-LD | LULUS | Diuji untuk `/` dan `/id`; JSON-LD di-parse di tes. |
| 15 | Tanpa JavaScript | LULUS | Kedua bahasa: navbar 5 item, headline, subteks (opacity 1), statistik final, foto pertama. |
| 16 | Navbar | LULUS | Services, Locations, About Us, Clients, Contact (ID: Layanan, Lokasi, Tentang Kami, Klien, Kontak); Contact ke `#contact`; scrollspy; smooth scroll; CTA transparan → oranye setelah scroll; tombol bahasa = link ke halaman yang sama. |
| 17 | Mega menu | LULUS | 4 layanan utama (2x2) + 1 pendukung, overlay redup, ikon per layanan, rapi di EN dan ID. |
| 18 | Menu mobile | LULUS | Animasi, accordion Services (5), CTA, tombol bahasa, Esc menutup, body terkunci. WhatsApp hanya tampil jika nomor diisi (tes baru). |
| 19 | Hero | LULUS | Overlay tanpa garis batas (lompatan kecerahan maks. 1,9/4 px), tinggi headline stabil, foto-label sinkron, indikator bisa diklik (aria-current benar), trust row hanya jika ada logo, statistik sejajar container, parallax hanya desktop. |
| 20 | prefers-reduced-motion | LULUS | Blur, Ken Burns, kilau badge, animasi menu, dan count-up tidak berjalan. |
| 21 | Keyboard dan ARIA | LULUS | Urutan Tab lengkap dengan focus ring; mega menu buka dengan Enter/panah, Esc mengembalikan fokus; aria-expanded dan aria-current benar. |
| 22 | Responsif 8 ukuran, `/` dan `/id` | LULUS | Tanpa scroll horizontal, tanpa elemen bertumpuk, hero = satu layar di semua ukuran desktop. Di 375x667 hero 753-759 px (mobile, boleh lebih dari satu layar). |
| 23 | Tanpa 404, 0 error console | LULUS | Di semua halaman dan ukuran yang diuji. |
| **E** | **SEO & performa** | | |
| 24 | robots.txt, sitemap.xml, llms.txt | LULUS | Sitemap memuat `/` dan `/id` dengan hreflang en/id/x-default; llms.txt mencantumkan kedua URL. |
| 25 | Lighthouse mobile 3x | LULUS | A11y/BP/SEO 100 dan CLS 0,003 di kedua bahasa (tabel di bawah). |
| 26 | Ukuran JS dan CSS (gzip) | LULUS | JS 22,9 KB (< 30 KB), CSS 9,3 KB. |
| 27 | Optimasi Lighthouse dari sisi kode | LULUS | Selesai: audit JS, animasi transform/opacity (blur headline tetap sesuai keputusan), komponen di luar layar pertama ditunda. Critical CSS dinonaktifkan sesuai keputusan (skrip disimpan). |
| **F** | **Git & deploy** | | |
| 28 | Identitas git | LULUS | Lokal: Mario Allesandro <marioallesandro2009@gmail.com>; satu-satunya author di riwayat. |
| 29 | .gitignore | LULUS | `.env`, SQLite, `_original/`, storage media tidak ter-commit; cadangan riwayat ada di luar proyek; `public/build` ter-commit. |
| 30 | Belum ada remote, belum di-push | LULUS | `git remote -v` kosong. |
| 31 | README lokal dan deploy cPanel | DIPERBAIKI | Langkah lokal (use-php83, migrate, storage:link) sudah benar. Ditambah cek `php -v` dan ekstensi (GD dll.) sebelum deploy; `media-library:regenerate` dan `optimize` sudah ada. |

## Lighthouse mobile (median 3 run)

Build lokal di belakang proxy gzip (setara hosting; `php artisan serve` sendiri tidak mengompres).

| Halaman | Performance | A11y | BP | SEO | FCP | LCP | TBT | CLS | Speed Index |
|---|---|---|---|---|---|---|---|---|---|
| `/` | 86 (80/86/87) | 100 | 100 | 100 | 1,25 s | 4,08 s | 20 ms | 0,003 | 2,55 s |
| `/id` | 83 (86/77/83) | 100 | 100 | 100 | 1,25 s | 4,01 s | 174 ms | 0,003 | 4,03 s |

Temuan tersisa:
- **LCP ~4,0 s** (subteks hero): dipengaruhi foto hero JPG 455 KB. Beres di hosting dengan WebP (`media-library:regenerate`); ukuran gambar responsif juga baru aktif dengan konversi.
- **Image delivery, next-gen formats, responsive images, cache lifetime, text compression**: otomatis beres di hosting (GD + header cache + gzip).
- **Server response time** dan **preconnect** (gambar dari `APP_URL` port 8000 sementara halaman lewat proxy port 8001): khas server lokal, tidak terjadi di hosting.
- **TBT / Speed Index berubah-ubah per run** (20-270 ms): server lokal satu proses; ukur ulang dengan PageSpeed Insights di hosting.
- **Unused JavaScript ~36 KiB**: bagian Alpine yang belum dipanggil saat load.

## Status pekerjaan berjalan

- **Pustaka ikon batch 1**: 30 ikon orisinal sudah di `resources/icons/ekasa/` (belum dipakai di situs), menunggu persetujuan Anda. Layanan dan statistik masih memakai ikon Lucide sampai pustaka ikon selesai.

## Perlu keputusan

1. Teks headline hero dan nama/deskripsi layanan: konfirmasi boleh dipakai atau tulis ulang (poin 6).
2. Sumber dan lisensi kelima foto seeder (status PERLU DICEK).
3. Persetujuan ikon batch 1.
