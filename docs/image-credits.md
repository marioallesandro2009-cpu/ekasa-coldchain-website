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

Logo asli Ekasa Cold Chain belum ada. Sampai logo asli diunggah lewat
**Pengaturan Situs › Umum**, seeder memakai placeholder buatan proyek ini:

| File | Isi |
|---|---|
| logo-placeholder-white.svg / logo-placeholder-navy.svg | Teks "PT Ekasa Cold Chain" (Plus Jakarta Sans 800, huruf besar-kecil biasa, diubah menjadi path SVG), tanpa simbol |
| favicon-placeholder.png, `public/favicon.ico` | Huruf "E" putih di kotak navy #0F2A4A |

Logo dan favicon dari situs referensi desain (coldspace.id) sudah dihapus dari
seeder, storage, dan media library.

## Font

- Plus Jakarta Sans (latin, bobot 400/500/600/700/800), woff2 dari Fontsource
  (`@fontsource/plus-jakarta-sans` 5.3.0). Lisensi SIL Open Font License 1.1.
  Disimpan di `resources/fonts/` (self-hosted, tanpa Google Fonts).

## Ikon

- Lucide (`lucide-static` 1.47.0), lisensi ISC. SVG disimpan di `resources/svg/lucide/`.
