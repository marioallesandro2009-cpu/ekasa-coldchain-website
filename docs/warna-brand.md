# Warna brand PT Ekasa Cold Chain

Sumber: brand guideline klien. Palet lama (navy, navy-dark, navy-light, ice, teal,
accent oranye) sudah diganti seluruhnya.

| Peran | Warna klien | Posisi di skala |
|---|---|---|
| Primary (Hijau Tua) | #0F6B46 | `brand-700` |
| Secondary (Hijau Sedang) | #38A169 | `brand-soft-500` |
| Neutral (Abu Tua) | #2D3748 | `neutral-700` |

Posisi dipilih dari lightness OKLCH warna klien (L 0,468 / 0,634 / 0,335). Untuk
neutral, posisi 700 ditetapkan oleh brief (`--color-ink` = neutral 700).

## Token (resources/css/app.css, `@theme`)

Skala 50-950 ditulis dalam `oklch()` (hex setara di komentar). Nilai klien ditulis
apa adanya di posisinya. Lightness disebar rata ke arah 50 dan 950, chroma
memuncak di tengah, dan hue tetap sama dengan warna klien.

| Step | brand | brand-soft | neutral |
|---|---|---|---|
| 50 | #F0FAF4 | #EFFAF2 | #F5F7FA |
| 100 | #E2F2E8 | #E0F3E6 | #E8EBEF |
| 200 | #C6E4D3 | #C3E6CF | #D0D5DE |
| 300 | #A0D2B6 | #9AD5AF | #B0B8C6 |
| 400 | #71B892 | #68BB8A | #8892A4 |
| 500 | #479D73 | **#38A169** | #647084 |
| 600 | #29835A | #09844E | #465164 |
| 700 | **#0F6B46** | #02693D | **#2D3748** |
| 800 | #015032 | #014F2C | #242C3A |
| 900 | #01341F | #01331B | #1A202B |
| 950 | #001C0F | #001C0C | #12161E |

Token semantik. Komponen hanya memakai token ini; tidak ada skala mentah atau hex
langsung di Blade.

| Token | Nilai | Hex |
|---|---|---|
| `--color-ink` | neutral 700 | #2D3748 |
| `--color-ink-muted` | neutral 500 | #647084 |
| `--color-surface` | neutral 50 | #F5F7FA |
| `--color-dark` | lightness neutral 900, hue hijau 165, chroma 0,022 | #16231D |
| `--color-brand` | brand 700 | #0F6B46 |
| `--color-brand-soft` | brand-soft 500 | #38A169 |
| `--color-highlight` | brand-soft 300 | #9AD5AF |
| `--color-cta-dark-bg` / `-text` | brand-soft / neutral 950 | #38A169 / #12161E |
| `--color-cta-dark-bg-hover` | cta-dark-bg 80% + putih | #60B487 |
| `--color-cta-light-bg` / `-text` | brand / putih | #0F6B46 / #FFFFFF |
| `--color-cta-light-bg-hover` | cta-light-bg 80% + hitam | #0C5638 |
| `--color-overlay` | dark 80% + hitam | #121C17 |

Admin (Pengaturan Situs › Warna) bisa mengubah tujuh token: brand, brand-soft,
highlight, dark, surface, ink, dan ink-muted. Nilai bawaannya ada di
`SiteSetting::COLOR_DEFAULTS`. Tes `keeps the default colors in sync with the
tokens in app.css` memastikan nilainya sama dengan CSS. Yang dikirim ke halaman
hanya warna yang berbeda dari bawaan, jadi dengan warna bawaan sumbernya hanya
`app.css`. Token CTA hover dan overlay mengikuti otomatis. Warna yang tersimpan
untuk palet lama dihapus oleh migrasi `switch_to_brand_palette`, dan latar section
"navy" diganti menjadi "dark" (Gelap).

## Pemetaan komponen

- **Navbar:** saat solid dan di panel gelap memakai `dark`. Garis menu aktif memakai
  `highlight`. CTA navbar tetap outline putih saat transparan, lalu menjadi CTA
  gelap setelah di-scroll.
- **Tombol:** `primary` memakai CTA gelap (tone dark: hero, navbar) atau CTA terang
  (tone light: section terang). Outline dan ghost di latar terang memakai `brand`.
- **Hero:** latar `dark`. Overlay rata, kiri, atas, dan bawah memakai `overlay`
  dengan titik opacity yang sama seperti sebelumnya. Titik badge, label
  rotatingText, label indikator aktif, dan progress memakai `highlight`. Bayangan
  CTA memakai `cta-dark-bg` 30%.
- **Bar statistik:** `dark` 55% dengan blur, ikon `highlight`.
- **Mega menu:** kotak ikon `brand` 10% dengan ikon `brand`. Saat hover, kotak ikon
  `brand` dan ikon putih. Tautan kartu foto `highlight` dengan gradasi `overlay`.
  Garis dan ring memakai `ink` 10% dan 5%.
- **Menu mobile:** lapisan animasi memakai `brand-soft` lalu `brand`, panel `dark`,
  penanda aktif dan ikon `highlight`.
- **Footer dan section gelap:** `dark`.
- **Focus ring:** `brand` di latar terang. Area dengan kelas `.on-dark` (header,
  hero, menu mobile, footer, section gelap) memakai `highlight`. Kartu mega menu
  kembali ke `brand`.
- **Badge shinyText:** tidak berubah (dasar putih 70%, kilau putih).

## Logo, meta, admin

- **Logo:** navbar transparan dan latar gelap memakai logo putih (versi terang).
  Petunjuk di admin: "Versi terang: ikon dan tulisan putih". Logo berwarna untuk
  latar terang.
- **Meta:** `meta theme-color` dan `/site.webmanifest` (baru, dari CMS: nama,
  favicon, `theme_color` dan `background_color`) memakai #0F6B46. Favicon memakai
  simbol logo yang warnanya sudah disesuaikan ke #0F6B46 dan #2D3748.
- **Placeholder:** gambar placeholder seeder memakai gradasi #0F6B46 ke #38A169.
- **Admin Filament:** `primary` memakai skala brand, digeser satu step sehingga
  primary-600 (tombol, tautan) tepat #0F6B46. Warna `info` biru lama dihapus
  (kembali ke bawaan Filament).
- **Badge hero bawaan:** EN "Fresher Tomorrow, Further Together", ID "Segar Hari
  Esok, Melangkah Bersama" (**terjemahan ID perlu dikonfirmasi klien**). Tetap
  bisa diubah di admin.

## Verifikasi

### Sisa warna lama

Hex, nama token, dan kelas lama (misalnya `#0F2A4A`, `bg-navy`, `text-ice`,
`--color-accent`, RGB `0x0F, 0x2A, 0x4A`):

| Lokasi | Sebelum | Sesudah |
|---|---|---|
| resources/css | 28 | 0 |
| resources/views (Blade) | 48 | 0 |
| app (PHP: model, admin, kontras, placeholder) | 22 | 0 |
| database/seeders | 1 (`'navy'`) | 0 |
| resources/js, config, routes | 0 | 0 |
| tests | 14 | 0 |

Total **99** tempat di kode (113 termasuk tes). CSS hasil build juga tidak memuat
hex atau token lama. Tidak ada template email di proyek ini.

### Kontras WCAG AA (pasangan tetap)

| Pasangan | Teks / latar | Rasio |
|---|---|---|
| CTA gelap: teks neutral 950 di #38A169 | #12161E / #38A169 | 5.58 |
| CTA gelap hover | #12161E / #60B487 | 7.23 |
| CTA terang: teks putih di #0F6B46 | #FFFFFF / #0F6B46 | 6.54 |
| CTA terang hover | #FFFFFF / #0C5638 | 8.74 |
| Teks putih di dark (navbar solid, menu mobile, footer) | #FFFFFF / #16231D | 16.25 |
| Highlight di dark (garis menu aktif, ikon menu mobile) | #9AD5AF / #16231D | 9.68 |
| Mega menu: judul (ink) di putih | #2D3748 / #FFFFFF | 11.99 |
| Mega menu: deskripsi (ink-muted) di putih | #647084 / #FFFFFF | 5.01 |
| Mega menu: deskripsi di surface (hover) | #647084 / #F5F7FA | 4.67 |
| Mega menu: ikon brand di kotak brand 10% | #0F6B46 / #E7F0ED | 5.63 |
| Mega menu: ikon putih di brand (hover) | #FFFFFF / #0F6B46 | 6.54 |
| Eyebrow brand di surface | #0F6B46 / #F5F7FA | 6.09 |
| Focus ring highlight di dark (non-teks, min. 3:1) | #9AD5AF / #16231D | 9.68 |
| Focus ring brand di putih (non-teks, min. 3:1) | #0F6B46 / #FFFFFF | 6.54 |

### Kontras di atas foto (diukur di browser, piksel latar terburuk)

Subteks (putih 85%), dengan 4 foto slide, gambar sangat terang, dan gambar putih
polos (`scripts/hero-overlay-check.mjs`):

| Viewport | 4 foto | Terang | Putih polos |
|---|---|---|---|
| 1920x1080 | 5.92 / 6.46 / 5.94 / 7.59 | 5.70 | 5.53 |
| 1440x900 | 5.84 / 6.46 / 5.95 / 6.72 | 5.70 | 5.53 |
| 1366x650 | 5.75 / 6.44 / 6.06 / 7.42 | 5.78 | 5.59 |
| 1280x800 | 5.77 / 6.46 / 6.04 / 6.67 | 5.78 | 5.60 |
| 1024x768 | 5.90 / 6.46 / 5.88 / 6.00 | 5.78 | 5.53 |
| 768x1024 | 5.37 / 6.02 / 5.30 / 5.48 | 5.44 | 5.30 |
| 390x844 | 5.36 / 6.02 / 5.61 / 6.17 | 5.45 | 5.30 |

Tidak ada tepi keras pada gradasi (lompatan kecerahan terbesar 2).

Elemen lain (foto slide 1 / putih polos):

| Elemen | 1440x900 | 390x844 | Syarat |
|---|---|---|---|
| Label rotatingText (highlight, 36-64 px tebal) | 3.64 / 3.47 | 3.93 / 3.91 | 3:1 (teks besar) |
| Label indikator aktif (highlight) | 9.26 / 8.70 | disembunyikan | 4.5:1 |
| Label indikator tidak aktif (putih 70%) | 7.70 / 7.53 | disembunyikan | 4.5:1 |
| Bar statistik: label (putih 75%) | 9.04 / 8.47 | 7.42 / 7.06 | 4.5:1 |
| Bar statistik: nilai (putih) | 15.22 / 13.89 | 11.95 / 11.12 | 4.5:1 |
| Menu navbar (putih 85%) | 11.09 / 10.67 | - | 4.5:1 |
| Mega menu: tautan kartu foto (highlight) | 6.14 / 5.37 | - | 4.5:1 |
| Mega menu: judul kartu foto (putih) | 7.22 / 5.89 | - | 4.5:1 |

Label rotatingText memenuhi AA untuk teks besar (3:1), tetapi tidak mencapai 4.5:1.
Ukurannya selalu minimal 36 px extra bold, jadi kategori teks besar berlaku.

### Lighthouse (lokal, `php artisan serve`, hasil build)

| Halaman | Accessibility | Best Practices | SEO |
|---|---|---|---|
| / | 100 | 100 | 100 |
| /id | 100 | 100 | 100 |

### Screenshot

`docs/screenshots/warna-brand/`: hero 1440x900 dan 390x844, navbar setelah
scroll, mega menu terbuka, dan menu mobile, masing-masing untuk EN dan ID, plus
admin dashboard dan Pengaturan Situs.
