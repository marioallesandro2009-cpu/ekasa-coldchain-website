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
| logo-color.png (597 x 229) | `logountukweb.png` | Logo horizontal, padding transparan dipangkas |
| logo-white.png (597 x 229) | Turunan logo-color.png | Semua warna diubah menjadi putih; jalan putih 45% agar tetap terpisah dari segi enam |
| favicon.png (512 x 512), `public/favicon.ico` (16/32/48) | `favicon.png` | Simbol segi enam, dipangkas persegi |

Versi logo bertumpuk (`mediastandarlogo.png`) belum dipakai karena belum ada slot yang cocok.

Logo dan favicon dari situs referensi desain (coldspace.id) sudah dihapus dari
seeder, storage, dan media library.

## Font

- Plus Jakarta Sans (latin, bobot 400/500/600/700/800), woff2 dari Fontsource
  (`@fontsource/plus-jakarta-sans` 5.3.0). Lisensi SIL Open Font License 1.1.
  Disimpan di `resources/fonts/` (self-hosted, tanpa Google Fonts).

## Ikon

- Lucide (`lucide-static` 1.47.0), lisensi ISC. SVG disimpan di `resources/svg/lucide/`.
