# Sumber gambar dan aset

## Foto seeder (`database/seeders/images/`)

Semua foto disediakan oleh pemilik proyek; tidak ada foto yang diunduh dari internet.
Metadata file asli (`_original/`) sudah kosong: tidak ada EXIF (kamera, fotografer,
hak cipta) maupun XMP, hanya profil warna sRGB. Karena itu sumber dan lisensinya tidak
bisa ditelusuri dari file dan **harus dikonfirmasi pemilik proyek sebelum situs
dipublikasikan**.

| File | Dimensi asli | Ukuran asli | Dipakai untuk | Sumber / lisensi |
|---|---|---|---|---|
| hero-cold-storage.jpg | 7491 x 5618 | 10.063 KB | Slide hero 1, gambar Open Graph | **PERLU DICEK** (sumber tidak diketahui) |
| hero-linehaul.jpg | 5900 x 3933 | 4.365 KB | Slide hero 2 | **PERLU DICEK** (sumber tidak diketahui) |
| hero-hyperlocal.jpg | 7008 x 4672 | 6.866 KB | Slide hero 3 | **PERLU DICEK** (sumber tidak diketahui) |
| hero-partnership.jpg | 4608 x 3456 | 3.064 KB | Slide hero 4 | **PERLU DICEK** (sumber tidak diketahui) |
| menu-services.jpg | 6240 x 4160 | 5.336 KB | Kartu mega menu | **PERLU DICEK** (sumber tidak diketahui) |

## Logo dan favicon (placeholder)

Logo Ekasa Cold Chain diberikan pemilik proyek (PNG transparan, **PERLU DICEK**:
file raster, belum ada versi vektor/SVG dari klien). Seeder memakai:

| File | Sumber | Isi |
|---|---|---|
| logo-color.png (597 x 229) | `logountukweb.png` | Logo horizontal, padding transparan dipangkas, warna disesuaikan ke palet brand |
| logo-white.png (597 x 229) | Turunan logo-color.png | Semua warna diubah menjadi putih; jalan putih 45% agar tetap terpisah dari segi enam |
| favicon.png (512 x 512), `public/favicon.ico` (16/32/48) | `favicon.png` | Simbol segi enam, dipangkas persegi, warna disesuaikan ke palet brand |

Versi logo bertumpuk (`mediastandarlogo.png`) belum dipakai karena belum ada slot yang cocok.

**Tindak lanjut ke klien:** minta file vektor (SVG) logo asli. Logo saat ini
raster (PNG, kemungkinan hasil generator gambar, tepinya tidak sepenuhnya
bersih) dan akan diganti begitu SVG tersedia: unggah di **Pengaturan Situs ›
Umum** (logo berwarna, logo putih, favicon) dan ganti file di
`database/seeders/images/`.

#### Penyesuaian warna logo ke palet brand

Warna hijau dan abu tua pada `logo-color.png` dan `favicon.png` dipetakan tepat ke
palet brand. Bentuk dan tepi tidak berubah: kanal alfa identik dengan file asli
(0 piksel berbeda). Salinan sebelum penyesuaian ada di
`database/seeders/images/_original/` (tidak ter-commit).

| Bagian | Sebelum (median, logo / favicon) | Rentang p5-p95 sebelum (R/G/B) | Sesudah |
|---|---|---|---|
| Hijau (segi enam, "COLD CHAIN") | #0F6140 / #0B6037 | 10-22 / 92-106 / 60-73 | #0F6B46 (rata, tanpa variasi) |
| Abu tua (jalan, "EKASA") | #1B222C / #1E262E | 24-31 / 30-38 / 40-48 | #2D3748 (rata, tanpa variasi) |

Cara: setiap piksel diproyeksikan ke garis abu tua-hijau lama lalu dipetakan ke
#2D3748-#0F6B46; hanya piksel campuran di batas kedua warna yang tetap bernada
antara. `logo-white.png` dan `public/favicon.ico` dibuat ulang dari hasil ini.
Perbandingan visual: `docs/screenshots/logo-warna-sebelum-sesudah.png`.

Logo dan favicon dari situs referensi desain (coldspace.id) sudah dihapus dari
seeder, storage, dan media library.

## Font

- Plus Jakarta Sans (latin, bobot 400/500/600/700/800), woff2 dari Fontsource
  (`@fontsource/plus-jakarta-sans` 5.3.0). Lisensi SIL Open Font License 1.1.
  Disimpan di `resources/fonts/` (self-hosted, tanpa Google Fonts).

## Ikon

- Lucide (`lucide-static` 1.47.0), lisensi ISC. SVG disimpan di `resources/svg/lucide/`.
