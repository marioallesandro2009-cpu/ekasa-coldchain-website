# Audit ulang tahap 1-2 (setelah logo, warna brand, dan ikon)

Tanggal: 25 September 2026. Lingkup: memeriksa, bukan menambah fitur. Perbaikan kecil
masuk commit "Audit ulang tahap 1-2". Perubahan ikon yang sudah Anda putuskan masuk
commit tersendiri.

**Ringkasan status:** 21 LULUS, 6 DIPERBAIKI, 0 GAGAL, 0 PERLU KEPUTUSAN (dua
keputusan sudah diambil di tengah audit, lihat bawah).

## Keputusan yang diambil selama audit

1. **Ikon batch 1 disetujui, kecuali handshake.** Handshake tetap ada di pustaka, tetapi
   bukan default. Ikon baru `chain-link` (EN "Linked partnership", ID "Kemitraan
   terhubung") menjadi default Logistic Partnership. Layanan dan statistik beralih dari
   Lucide ke ikon Ekasa:

   | Tempat | Ikon |
   |---|---|
   | Cold Storage | snowflake |
   | Linehaul Transport | reefer-truck |
   | Hyperlocal Fulfilment | map-pin |
   | Logistic Partnership | chain-link |
   | Omnichannel Integration | route (tidak ada ikon "network" di stok Ekasa) |
   | Statistik | map-pin, warehouse, thermometer-snowflake, badge |

   Pratinjau chain-link: `docs/screenshots/ikon/chain-link-pratinjau.png`.
2. **Tiga file gambar di root** (`favicon (2).png`, `logountukweb (2).png`,
   `mediastandarlogo.png`) dipindahkan ke `database/seeders/images/_original/`
   (di-ignore git), tidak dihapus.

## Hasil pemeriksaan

| No | Pemeriksaan | Status | Catatan |
|---|---|---|---|
| **A** | **Kode & build** | | |
| 1 | Dependency sesuai stack | LULUS | Composer: Laravel 13.33, PHP ^8.3, Filament 5, Spatie Media Library 11. npm (dev): Vite 8.3, Tailwind 4, Alpine 3 (+intersect), laravel-vite-plugin, playwright-core dan jSquash untuk skrip lokal. Tidak ada inertia, vue, react, gsap, motion, three, atau fortify. `@vue/reactivity` di lock hanya dependency internal Alpine. |
| 2 | migrate:fresh --seed, build, Pest, Pint, PHPStan | DIPERBAIKI | `migrate:fresh --seed` bersih (dijalankan di database sementara agar data lokal tidak hilang). Build tanpa warning. Pint dan PHPStan lulus. Pest sebelumnya 1 gagal ("conversions are off"), dan penyebabnya bug nyata: jika konversi WebP dimatikan setelah file WebP dibuat, `getUrl()` melempar exception. Diperbaiki di `MediaPresenter::hasConversion()`. Hasil akhir Pest: lihat bagian Verifikasi. |
| 3 | Tanpa console.log/dd/dump, kode dikomentari, file sisa; /dev tidak aktif di production | LULUS | Tidak ada debug call. `//` yang tersisa hanya placeholder kosong bawaan Laravel. Route `/dev/icons` hanya terdaftar di `local` (ada tesnya). 22 ikon Lucide konten yang tidak terpakai lagi dihapus bersama peralihan ikon. |
| **B** | **Warna brand** | | |
| 4 | Tidak ada sisa warna lama | LULUS | `git grep` hex lama (#0F2A4A, #0A1D33, #153A63, #4FB3E8, #2BB3A6, #F26A21, #F47C3C, #F5F8FB, #1A2B3C, #5B6B7B), RGB-nya, dan nama token (navy, ice, teal, accent) di semua file ter-commit selain docs: 0 temuan di CSS, Blade, JS, config, seeder, admin, dan hasil build. Satu-satunya kemunculan ada di migrasi `switch_to_brand_palette` yang mengonversi data lama (memang harus menyebut "navy"). Meta, manifest, dan favicon memakai #0F6B46. Di docs, warna lama hanya muncul di laporan tahap 1-2 lama (arsip). |
| 5 | Komponen hanya memakai token semantik | LULUS | 0 hex/rgb/oklch dan 0 kelas skala mentah (`brand-700`, `neutral-500`, dll.) atau palet bawaan Tailwind di Blade/JS. CSS di luar `@theme` hanya memakai token semantik. Pengecualian yang wajar: `#000` sebagai mask shinyText (bukan warna tampil) dan halaman lokal `/dev/icons`. |
| 6 | Tabel kontras WCAG AA | LULUS | Semua pasangan lulus, lihat tabel di bawah. Label rotatingText 3,47:1 lulus kriteria teks besar (3:1; minimal 36 px extra bold). |
| 7 | theme-color, site.webmanifest, admin Filament | LULUS | `theme-color` #0F6B46, manifest `theme_color`/`background_color` #0F6B46, Filament primary-600 = #0F6B46. |
| **C** | **Logo & ikon** | | |
| 8 | Logo putih di latar gelap, logo warna di latar terang; tajam, proporsional, lebar dibatasi | LULUS | Navbar (transparan dan solid) dan menu mobile memakai logo putih. Belum ada tempat logo di latar terang (logo warna disiapkan untuk section terang dan Open Graph). File 597 x 229 ditampilkan 94 x 36 (6x, tajam di retina), rasio dari dimensi asli, lebar maks. 200/180 px, ada WebP 600 px. Catatan: tulisan "COLD CHAIN" di tinggi 36 px hanya sekitar 4 px; bisa dibesarkan ke 44-48 px jika diinginkan. |
| 9 | Favicon 16/32/48 (.ico), 180, 192, 512, manifest | DIPERBAIKI | Sebelumnya hanya PNG 512 (juga dipakai sebagai apple-touch-icon). Sekarang favicon yang diunggah menghasilkan 32, 180, dan 192 px (konversi Spatie). HTML: icon 32 + 512, apple-touch-icon 180. Manifest: 192 dan 512. `public/favicon.ico` berisi 16/32/48. Ada tesnya. |
| 10 | Ikon layanan/statistik sesuai data, currentColor, Logistic Partnership chain-link | DIPERBAIKI | Sesuai keputusan 1: layanan dan statistik memakai ikon Ekasa (stroke 1.75, `currentColor`), Logistic Partnership memakai chain-link. Data lama dipetakan lewat migrasi `switch_to_ekasa_icons`. Pilihan ikon di admin diambil dari manifest Ekasa. |
| 11 | credits.md dan image-credits.md mutakhir | DIPERBAIKI | Baris logo di credits.md masih "placeholder teks", sekarang "logo raster dari klien, minta SVG". Baris Lucide diperbarui (hanya ikon antarmuka). Foto tetap **PERLU DICEK**. |
| **D** | **Identitas & hak cipta** | | |
| 12 | Tidak ada "coldspace" di luar pengecualian | LULUS | 0 di kode, seeder, `/`, `/id`, robots.txt, sitemap.xml, llms.txt, dan site.webmanifest. Di seluruh riwayat git hanya ada di docs (referensi desain) dan `tests/Feature/SeoFilesTest.php`. Tidak ada di pesan commit maupun nama file. |
| 13 | Tidak ada tagline/teks/logo/screenshot Coldspace; layanan orisinal + DUMMY | LULUS | Badge baru "Fresher Tomorrow, Further Together". Headline dipakai sesuai keputusan Anda. Deskripsi layanan orisinal dan bertanda DUMMY (juga statistik dan section placeholder). Screenshot di docs hanya dari situs ini. |
| **E** | **Admin** | | |
| 14 | Semua tab dan resource tersimpan, tampil, dua bahasa berdampingan, validasi upload, cache dua bahasa | LULUS | Dicakup tes: semua halaman admin tampil; tab Umum/Hero/Mega Menu/Teks/Warna/SEO/Terjemahan tersimpan dan tampil di situs; Layanan, Slide Hero, Statistik, Section, dan Logo Klien; field EN/ID berdampingan; upload ditolak jika format salah atau lebih dari 4000 px; `FrontendData::flush()` menghapus cache semua bahasa saat model disimpan atau dihapus. Pustaka Ikon di admin belum ada (dikerjakan setelah audit). |
| 15 | Tidak ada field admin yang tidak dipakai | LULUS | Semua field terpakai: email/telepon (footer, JSON-LD, llms), Instagram/LinkedIn (JSON-LD `sameAs`), WhatsApp (menu mobile, llms), 7 warna, logo, favicon, OG. |
| **F** | **Halaman publik** | | |
| 16 | View-source: navbar, hero, meta, canonical, hreflang, og:locale, JSON-LD | LULUS | `/` dan `/id`: `lang`, title, description, canonical, hreflang en/id/x-default, og:locale en_US/id_ID (+ alternate), theme-color, manifest. JSON-LD valid: Organization (legalName PT Ekasa Cold Chain), WebSite, WebPage (inLanguage sesuai bahasa), ItemList. |
| 17 | Tanpa JavaScript | LULUS | Header solid, H1 lengkap dengan label pertama, statistik final, foto slide 1 tampil, indikator tersembunyi (kedua bahasa). Screenshot `*-tanpa-js-*`. |
| 18 | Perilaku navbar, mega menu, menu mobile, hero, statistik, trust row | LULUS | `scripts/browser-check.mjs` untuk `/` dan `/id`: CTA outline di atas lalu solid setelah 40 px, navbar 68 px, scrollspy, mega menu hover/klik/Esc, menu mobile (fokus ke tombol tutup, body terkunci, Esc), tepi kiri logo/headline/statistik sejajar (104 px), dan 0 kegagalan. Trust row tidak dirender saat kosong (ada tesnya). |
| 19 | prefers-reduced-motion dan keyboard | LULUS | Reduced motion: tanpa blur/zoom, angka final langsung tampil, tidak ada konten tersembunyi. Keyboard: semua tab stop punya outline, mega menu terbuka dengan Enter, Esc mengembalikan fokus ke pemicu. |
| 20 | Responsif di 8 ukuran, / dan /id | LULUS | 375, 390, 768, 1024, 1280, 1366x650, 1440, dan 1920: scrollX 0, tanpa overflow atau teks terpotong, hero tepat satu layar (bar statistik berakhir di tepi bawah). |
| 21 | 0 request 404, 0 error console | LULUS | Di semua pemeriksaan browser (kedua bahasa, admin, screenshot). |
| **G** | **SEO & performa** | | |
| 22 | robots.txt, sitemap.xml, llms.txt | LULUS | robots: Allow /, Disallow /admin dan /livewire, tautan sitemap. Sitemap: `/` dan `/id`, masing-masing dengan hreflang en/id/x-default dan lastmod. llms.txt: ringkasan, 5 layanan, bahasa, kontak. |
| 23 | Lighthouse mobile 5 run | LULUS | A11y/BP/SEO 100 di 10 dari 10 run, CLS 0,002. Performance median 83/83; lihat tabel dan catatan di bawah. |
| 24 | Ukuran JS/CSS halaman depan (gzip) | LULUS | JS 22,7 KB (< 30 KB), CSS 9,5 KB. |
| **H** | **Git & deploy** | | |
| 25 | Identitas git di semua commit | LULUS | Semua commit (author dan committer): Mario Allesandro <marioallesandro2009@gmail.com>. |
| 26 | .gitignore, public/build ter-commit, status bersih, tanpa remote | DIPERBAIKI | Tiga file gambar di root membuat status tidak bersih; dipindahkan sesuai keputusan 2. `.gitignore` benar (.env, vendor, node_modules, `_original/`, storage), `public/build` ter-commit, `git remote -v` kosong. Status bersih setelah commit. |
| 27 | README akurat | DIPERBAIKI | Ditambah bagian warna brand dan logo, ukuran favicon dari `media-library:regenerate`, dan `hero-overlay-check.mjs`. Langkah lokal dan deploy cPanel lainnya sudah sesuai. |

## Tabel kontras (ringkas)

| Pasangan | Rasio | Syarat |
|---|---|---|
| CTA di latar gelap (neutral 950 di #38A169) / hover | 5,58 / 7,23 | 4,5 |
| CTA di latar terang (putih di #0F6B46) / hover | 6,54 / 8,74 | 4,5 |
| CTA outline navbar di atas foto: teks putih / cincin putih 60% (foto; putih polos) | 8,64 / 4,32; 8,20 / 4,16 | 4,5 / 3 |
| Subteks hero di atas overlay: 4 foto (terburuk semua viewport) / putih polos | 5,30 / 5,30 | 4,5 |
| Label rotatingText (foto / putih polos, 1440) | 3,64 / 3,47 | 3 (teks besar) |
| Indikator aktif (highlight) / tidak aktif (putih 70%) | 8,70 / 7,53 | 4,5 |
| Bar statistik: label (putih 75%) / nilai (putih) | 7,06 / 11,12 | 4,5 |
| Mega menu: judul / deskripsi / deskripsi saat hover | 11,99 / 5,01 / 4,67 | 4,5 |
| Mega menu: ikon di kotak brand 10% / ikon putih saat hover | 5,63 / 6,54 | 3 |
| Mega menu kartu foto: tautan highlight / judul (putih polos) | 5,37 / 5,89 | 4,5 |
| Teks putih di latar gelap (navbar solid, menu mobile, footer) | 16,25 | 4,5 |
| Focus ring: highlight di latar gelap / brand di latar terang | 9,68 / 6,54 | 3 |

Rincian lengkap dan metode: `docs/warna-brand.md`.

## Lighthouse mobile (median 5 run)

Build lokal di belakang proxy gzip lokal. URL :8000 ditulis ulang ke :8001 agar semua
aset datang dari satu origin, seperti di hosting.

| Halaman | Performance (5 run) | A11y | BP | SEO | FCP | LCP | TBT | CLS | Speed Index |
|---|---|---|---|---|---|---|---|---|---|
| `/` | 83 (83/85/77/88/80) | 100 | 100 | 100 | 1,27 s | 3,74 s | 237 ms | 0,002 | 3,78 s |
| `/id` | 83 (82/83/64/84/89) | 100 | 100 | 100 | 1,20 s | 3,71 s | 256 ms | 0,002 | 3,95 s |

- **LCP ~3,7 s** (elemen LCP: subteks hero): foto hero JPG dan konversi WebP lokal. Ini
  lebih baik dari audit sebelumnya (4,0 s), tetapi masih perlu diukur di hosting dengan
  WebP dan gzip.
- **TBT berubah-ubah per run** (74-745 ms): server `php artisan serve` satu proses di
  laptop yang juga menjalankan Lighthouse. Median lebih tinggi dari audit sebelumnya (20/174
  ms), dan di audit ini tidak ada perubahan JavaScript kecuali penghapusan handler bahasa,
  jadi selisihnya dianggap variasi lokal. Ukur ulang dengan PageSpeed Insights di hosting.
- Sisa temuan (cache lifetime, next-gen images, server response time) khas server lokal.

## Status pekerjaan berjalan

- **Pustaka ikon batch 1:** disetujui (kecuali handshake sebagai default). Chain-link sudah
  ditambahkan, total 31 ikon orisinal.
- **Pustaka Ikon di admin:** belum dibuat; dikerjakan setelah audit ini.
- **Batch 2:** belum dimulai, menunggu persetujuan Anda.

## Verifikasi

- Pest: 79 tes lulus (1243 assertion), termasuk tes "conversions are off" yang sebelumnya gagal.
- Pint dan PHPStan: lulus.
- Screenshot: `docs/screenshots/audit-v2/`: `/` dan `/id` hero 1440x900, 390x844, dan 1366x650;
  navbar setelah scroll; mega menu terbuka; menu mobile; tanpa JavaScript (1440 dan 390);
  dashboard admin dan daftar Layanan.
