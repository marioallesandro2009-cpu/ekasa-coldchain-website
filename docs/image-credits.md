# Sumber gambar dan aset

## Foto seeder (`database/seeders/images/`)

Foto beranda di bawah ini disediakan pemilik proyek. Lima foto untuk layanan
(Tahap 3) diunduh dari Pexels; lihat bagian berikutnya. Metadata file asli (`_original/`) sudah kosong: tidak ada EXIF (kamera, fotografer,
hak cipta) maupun XMP, hanya profil warna sRGB. Karena itu sumber dan lisensinya tidak
bisa ditelusuri dari file dan **harus dikonfirmasi pemilik proyek sebelum situs
dipublikasikan**.

| File | Dimensi asli | Ukuran asli | Dipakai untuk | Sumber / lisensi |
|---|---|---|---|---|
| hero-truck.jpg | 2400 x 1952 | 563 KB | Slide hero 1 (Cold Storage), gambar Open Graph | Pexels License (kanvas diperpanjang 22% ke bawah agar truk tidak tertutup bar statistik), [#93398](https://www.pexels.com/photo/white-dump-truck-near-pine-tress-during-daytime-93398/), fotografer 500photos.com (nama diambil dari daftar pencarian Pexels, **cek ulang di halaman foto**) |
| hero-linehaul.jpg | 5900 x 3933 | 4.365 KB | Slide hero 2 | **PERLU DICEK** (sumber tidak diketahui) |
| hero-hyperlocal.jpg | 7008 x 4672 | 6.866 KB | Slide hero 3 | **PERLU DICEK** (sumber tidak diketahui) |
| hero-partnership.jpg | 2400 x 1600 | ~480 KB | Slide hero 4 (Logistic Partnership) | Pexels License, [#8994766](https://www.pexels.com/photo/8994766/), fotografer Braeson Holland (nama dari daftar pencarian Pexels, **cek ulang di halaman foto**) |
| menu-services.jpg | 6240 x 4160 | 5.336 KB | Kartu mega menu | **PERLU DICEK** (sumber tidak diketahui) |

### Foto untuk Tahap 3 (belum dipakai seeder, belum di-commit)

Nama file diperbaiki dari ekstensi ganda (misalnya `location-cakung.jpg.jpg`), dan
isi tiap file sudah dicek cocok dengan namanya. Semua JPG sudah diperkecil dengan
`scripts/optimize-seed-images.mjs` (file asli di `_original/`, tidak ter-commit).

**Diunduh dari Pexels** (Pexels License: boleh dipakai komersial, tanpa wajib
atribusi; dilarang menjual ulang foto apa adanya atau menyiratkan dukungan orang di
foto). Menggantikan foto dari klien yang tidak cocok.

| File | Menggantikan | Foto Pexels | Fotografer |
|---|---|---|---|
| service-cold-storage-detail.jpg | Peti kayu dan traktor (bukan cold storage) | [#5953713](https://www.pexels.com/photo/a-worker-in-a-storage-room-5953713/) ruang dingin putih | Anna Shvets |
| service-cold-storage-hero.jpg | Foto potret (hero butuh lanskap) | [#36126305](https://www.pexels.com/photo/industrial-storage-warehouse-interior-view-36126305/) lorong rak gudang | Adrien Olichon |
| service-linehaul-hero.jpg | Barisan mobil pikap (bukan truk linehaul) | [#10594292](https://www.pexels.com/photo/asphalt-road-between-fields-10594292/) jalan raya dengan truk di kejauhan | BREAKS OUT |
| service-partnership-hero.jpg | Foto potret (hero butuh lanskap) | [#4487361](https://www.pexels.com/photo/warehouse-workers-carrying-boxes-4487361/) dua pekerja membawa kardus | Tiger Lily |
| service-hyperlocal-card.jpg | Kurir berkaos merah (nuansa hangat, tidak senada dengan kartu lain) | [#6169135](https://www.pexels.com/photo/delivery-man-going-out-from-a-van-6169135/) kurir naik ke van putih polos di jalan perumahan | Tima Miroshnichenko |

**Kartu Linehaul memakai foto armada Ekasa**, bukan file tersendiri:
`service-linehaul-card.jpg` (trailer besar) dihapus. Kartu 4:3 menampilkan
`fleet-fe71-long-side.jpg` dengan crop tampilan (object-fit: cover, titik fokus
horizontal sekitar 70%): tulisan EKASA COLD CHAIN, strip merah, dan roda belakang
utuh, kabin terpotong. File aslinya tidak diedit. FE74 tidak dipakai karena unit
pendinginnya berlogo pabrikan lain. Pratinjau crop:
`docs/screenshots/foto-pengganti/armada-crop-4x3.png`. **Ganti dengan foto armada asli
yang sudut/rasionya lebih pas** begitu tersedia dari klien.

**Disediakan pemilik proyek** (sumber dan lisensi **PERLU DICEK**):

| File | Isi | Catatan |
|---|---|---|
| location-cakung.jpg, location-pulo-gadung.jpg, location-tanjung-priok.jpg | Gedung gudang, silo, loading dock | **Foto ilustrasi generik, BUKAN foto gudang Ekasa yang sebenarnya.** Perlu diganti foto asli lokasi Ekasa sebelum situs dipublikasikan. |
| service-linehaul-detail.jpg | Trailer besar di dock | **Foto sementara.** Truknya trailer besar, beda dengan armada Ekasa (truk boks menengah). Sebaiknya diganti foto truk boks menengah. |
| fleet-fe74-long-side.png, fleet-fe71-long-side.jpg | Truk boks Ekasa FE 74 LONG dan FE71 LONG, tampak samping | Dari klien, isinya tidak diedit. FE71 (JPEG, jadi nama barunya `.jpg`) ikut dikompres ulang q82: 107 kB -> 96 kB, tanpa artefak yang terlihat (`docs/screenshots/foto-pengganti/fe71-kompresi-zoom.png`). FE74 (PNG) tidak diproses skrip. |
| service-cold-storage-card.jpg, service-hyperlocal-hero.jpg, service-hyperlocal-detail.jpg, service-linehaul-detail.jpg, service-partnership-card.jpg, service-partnership-detail.jpg | Sesuai nama layanan | Sumber belum diketahui. |

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

- Lucide (`lucide-static` 1.47.0), lisensi ISC, hanya untuk ikon antarmuka (panah, chevron, menu, tutup, chat). SVG disimpan di `resources/svg/lucide/`. Ikon layanan dan statistik adalah ikon Ekasa orisinal (`resources/icons/ekasa/`).
