# Laporan Tahap 1-2: Fondasi, Panel Admin, Navbar, dan Hero

Tanggal: 23 September 2026 (diperbarui 24 September 2026). Proyek: website PT Ekasa Cold Chain (coldspace.id hanya referensi desain).

---

## 1. Ringkasan

Tahap 1-2 membangun fondasi website Ekasa Cold Chain di Laravel 13. Halaman publiknya dirender penuh di server dengan Blade, dan Alpine.js hanya menambahkan animasi dan interaksi, sehingga seluruh konten terbaca oleh mesin pencari dan crawler AI tanpa perlu Node.js atau SSR di hosting.

Klien mengelola konten lewat panel Filament di `/admin`:
- pengaturan situs (Umum, Hero, Mega Menu, SEO);
- layanan, slide hero, dan statistik hero, lengkap dengan urutan drag-and-drop dan toggle aktif.

Gambar dikelola spatie/laravel-medialibrary. Konversi WebP otomatis aktif begitu server memiliki GD/Imagick; tanpa itu situs tetap berjalan dengan file original. Data halaman depan di-cache dan cache-nya otomatis dihapus setiap kali admin menyimpan perubahan.

Navbar dan hero sudah lengkap sesuai spesifikasi:
- navbar transparan yang berubah navy saat scroll, dengan mega menu, scrollspy, dan menu mobile staggered;
- slideshow hero dengan crossfade yang dikendalikan satu indeks, Ken Burns, indikator berlabel, dan statistik count-up.

Semua itu tetap rapi saat JavaScript dimatikan atau pengguna memilih reduced motion.

SEO: meta lengkap, canonical, Open Graph/Twitter, JSON-LD (Organization, WebSite, WebPage, daftar Service), serta `robots.txt`, `sitemap.xml`, dan `llms.txt` dinamis.

Hasil audit:
- Lighthouse Accessibility, Best Practices, dan SEO masing-masing 100.
- CLS 0,002.
- JS halaman depan 22,4 KB gzip.
- Performance lokal masih rendah karena foto original belum dikonversi WebP (lihat bagian 11).

---

## 2. Stack & versi aktual

Diambil dari `composer.lock` dan `package-lock.json`.

| Paket | Versi |
|---|---|
| PHP (lokal) | 8.3.35 NTS |
| laravel/framework | 13.33.0 |
| filament/filament | 5.8.4 |
| filament/spatie-laravel-media-library-plugin | 5.8.4 |
| spatie/laravel-medialibrary | 11.23.8 |
| spatie/image | 3.9.6 |
| livewire/livewire (dipakai Filament) | 4.4.6 |
| pestphp/pest / pest-plugin-laravel | 4.7.8 / 4.1.0 |
| phpunit/phpunit | 12.5.33 |
| laravel/pint / larastan | 1.32.1 / 3.12.2 |
| alpinejs / @alpinejs/intersect | 3.17.4 / 3.17.4 |
| tailwindcss / @tailwindcss/vite | 4.3.3 / 4.3.3 |
| vite / laravel-vite-plugin | 8.3.0 / 3.2.0 |
| playwright-core (dev, hanya untuk audit) | 1.63.0 |

Tidak ada lagi Inertia, Vue, React, GSAP, motion, three, @vueuse, Fortify, maupun Wayfinder.

---

## 3. Lingkungan lokal

- **PHP:** `C:\Users\Acer\php83\php.exe` (8.3.35), diaktifkan per terminal lewat `use-php83.ps1` / `use-php83.sh`. PHP 8.2 milik XAMPP tetap ada di PATH Machine dan tidak dipakai.
- **GD:** tidak tersedia. Smart App Control Windows memblokir `php_gd.dll` untuk PHP 8.3. Imagick juga tidak ada.
- **Status WebP: nonaktif di lokal** (`IMAGE_CONVERSIONS=auto` → tidak ada driver). Halaman admin menampilkan badge "Konversi WebP: Nonaktif (GD tidak tersedia)".
- **Database lokal:** SQLite (`database/database.sqlite`). Konfigurasi MySQL tersedia di `.env.example`.
- **Queue:** `QUEUE_CONNECTION=sync`. Konversi media juga `nonQueued`.

---

## 4. Struktur folder penting

```
app/
  Filament/Pages/ManageSiteSettings.php      halaman Pengaturan Situs (tab Umum/Hero/Mega Menu/SEO)
  Filament/Resources/{Services,HeroSlides,HeroStats}/
  Http/Controllers/HomeController.php        view "home"
  Http/Controllers/SeoController.php         robots.txt, sitemap.xml, llms.txt
  Http/Middleware/EnforceCanonicalHost.php   redirect ke skema+host APP_URL (produksi)
  Models/{SiteSetting,Service,HeroSlide,HeroStat,User}.php
  Models/Concerns/{FlushesFrontendCache,RegistersWebpConversions,Sortable}.php
  Support/{FrontendData,ImageConversions,MediaPresenter,Seo,StructuredData,Icon,ServiceIcons,PlaceholderImage}.php
config/navigation.php                         struktur menu (sengaja di kode)
config/site.php                               IMAGE_CONVERSIONS, admin seeder, WhatsApp, cache key
database/seeders/                             seeder + images/ (foto dari pemilik proyek)
resources/
  css/app.css                                 token desain, font-face, animasi CSS
  fonts/                                      Plus Jakarta Sans woff2 (self-hosted)
  js/app.js                                   registrasi Alpine terpusat
  js/alpine/                                  navbar, hero, rotatingText, blurText, countUp, animatedContent, magnet
  svg/lucide/                                 ikon SVG
  views/layouts/app.blade.php                 layout utama (<x-layouts::app>)
  views/components/layout/                    navbar, mega-menu, mobile-menu, logo, footer
  views/components/sections/hero.blade.php
  views/components/ui/                        button, container, section, section-heading, icon
  views/home.blade.php
  views/seo/{sitemap,llms}.blade.php
public/.htaccess                              HTTPS, gzip, cache
public/build/                                 hasil build (di-commit, untuk cPanel tanpa Node)
scripts/{browser-check,contrast-check}.mjs    audit browser (Chrome headless)
tests/Feature/                                tes Pest
docs/                                         laporan, image-credits, screenshots
```

---

## 5. Database

| Tabel | Kolom | Fungsi |
|---|---|---|
| `site_settings` (1 baris, model singleton) | company_name, whatsapp_number, email, phone, instagram_url, linkedin_url, cta_label, hero_badge, hero_headline, hero_subtext, hero_slide_duration, mega_menu_title, mega_menu_link_label, seo_title, seo_description | Semua teks global dan SEO. Media: logo_white, logo_color, favicon, mega_menu_image, og_image. |
| `services` | title, slug (unik), short_description (maks 60), icon (nama Lucide), anchor, sort_order, is_active | Isi mega menu, menu mobile, JSON-LD, llms.txt |
| `hero_slides` | label, image_alt, sort_order, is_active | Label rotating text + foto (media "image") |
| `hero_stats` | value (decimal, nullable), prefix, suffix, text_value, label, use_count_up, sort_order, is_active | Bar statistik hero |
| `media` | (spatie) | File dan konversi |
| `users`, `cache`, `jobs`, `sessions`, … | bawaan Laravel | Admin, cache halaman depan |

**Isi seeder:**
- **Admin:** dari `ADMIN_EMAIL` / `ADMIN_PASSWORD` di `.env`. Dilewati jika kosong.
- **Settings:** teks default, `company_name` "Ekasa Cold Chain", `seo_title` "Ekasa Cold Chain | Cold Chain Logistics in Indonesia" (EN) / "Ekasa Cold Chain | Logistik Rantai Dingin di Indonesia" (ID), WhatsApp dari `WHATSAPP_NUMBER` (boleh kosong), serta logo, favicon, gambar mega menu, dan OG image dari `database/seeders/images/`.
- **Layanan:** 5 data (Cold Storage, Linehaul Transport, Hyperlocal Fulfilment, Logistic Partnership, Omnichannel Integration) lengkap dengan ikon.
- **Slide hero:** 4 slide dengan foto dari pemilik proyek.
- **Statistik:** 4 data dengan komentar `DUMMY` (600+, 15+, -25°C to 6°C, 100+).

---

## 6. Panel admin (`/admin`, label Bahasa Indonesia)

| Menu | Isi yang bisa diedit klien |
|---|---|
| **Pengaturan Situs** | Tab **Umum**: nama perusahaan, WhatsApp, email, telepon, URL Instagram & LinkedIn, label CTA, logo putih, logo berwarna, favicon. Tab **Hero**: badge, headline, subteks, durasi slide (2–30 detik). Tab **Mega Menu**: judul kartu, label tautan, gambar. Tab **SEO**: judul halaman, meta description, gambar Open Graph. Badge status konversi WebP tampil di atas form. |
| **Konten Beranda › Slide Hero** | Label layanan, alt foto, foto, aktif. Tabel: pratinjau foto, toggle aktif, drag-and-drop urutan. |
| **Konten Beranda › Statistik Hero** | Label, prefiks, nilai angka, sufiks, nilai teks, count up, aktif. Tabel: nilai terformat, toggle, drag-and-drop. |
| **Konten Beranda › Layanan** | Judul, slug, deskripsi singkat (maks 60), ikon (dropdown 22 ikon Lucide), anchor, aktif. Tabel: toggle, drag-and-drop. |

**Petunjuk ukuran di form:**

| Gambar | Ukuran ideal | Format dan batas |
|---|---|---|
| Hero | 1920 x 1080 px | jpg/png/webp, maks 5 MB |
| Mega menu | 800 x 600 px | jpg/png/webp, maks 5 MB |
| Open Graph | 1200 x 630 px | jpg/png/webp, maks 5 MB |
| Favicon | 512 x 512 px | png/svg/ico, maks 1 MB |
| Logo | PNG/SVG transparan | maks 5 MB |

---

## 7. Route publik

| Route | Fungsi |
|---|---|
| `GET /` | Beranda (navbar, hero, section placeholder, footer) |
| `GET /robots.txt` | Izinkan semua crawler (termasuk AI), `Disallow: /admin` dan `/livewire`, tautan sitemap |
| `GET /sitemap.xml` | Sitemap otomatis, `lastmod` dari perubahan data terakhir |
| `GET /llms.txt` | Ringkasan perusahaan, layanan, dan kontak dalam Markdown |
| `GET /up` | Health check Laravel |
| `/admin/*` | Panel Filament (login wajib) |

Route development `/dev/components` sudah dihapus.

---

## 8. Komponen Blade dan Alpine

### Blade

| Komponen | Fungsi | Props |
|---|---|---|
| `<x-layouts::app>` | Layout: meta, OG, canonical, JSON-LD, preload, font | `seo`, `settings`, `jsonLd` |
| `<x-layout.navbar>` | Header + mega menu + menu mobile | `settings`, `services` |
| `<x-layout.mega-menu>` | Panel Services (5 layanan + kartu foto) | `services`, `megaMenu`, `contactId` |
| `<x-layout.mobile-menu>` | Panel layar penuh, accordion `<details>`, CTA, WhatsApp | `settings`, `services`, `items`, `contactId` |
| `<x-layout.logo>` | Logo `<picture>` (WebP jika ada), rasio dari dimensi asli | `logo`, `name`, `height` |
| `<x-layout.footer>` | Footer minimal (hak cipta, email/telepon jika diisi) | `settings` |
| `<x-sections.hero>` | Hero + bar statistik | `settings`, `slides`, `stats` |
| `<x-ui.container>` | Lebar 1280px, padding 16/24px | `as` (default `div`) |
| `<x-ui.section>` | Section dengan jarak vertikal 64px (mobile) / 96px (desktop) | `id`, `variant` (white/surface/navy), `contained` |
| `<x-ui.section-heading>` | Eyebrow + judul + deskripsi, animasi muncul | `eyebrow`, `title`, `description`, `align` (left/center), `level`, `tone` (light/dark), `id` |
| `<x-ui.button>` | Tombol/tautan | `variant` (primary/secondary/ghost), `tone` (dark/light), `size` (md/lg), `href`, `icon` |
| `<x-ui.icon>` | Ikon Lucide inline | `name` |

### Alpine (`resources/js/alpine/`, didaftarkan di `app.js`)

| Nama | Fungsi | Atribut/pemakaian |
|---|---|---|
| `navbar` | Status scroll (> 40px), scrollspy (IntersectionObserver, garis 1px di bawah navbar), mega menu (hover dengan tutup 150ms, klik, ArrowDown, Esc, klik di luar), menu mobile (buka/tutup, body lock, Esc, focus trap) | `x-data="navbar"` pada pembungkus header |
| `hero` | Satu indeks untuk foto + label; progress CSS sebagai timer; pause saat mouse di atas baris indikator (bukan sentuhan) dan saat tab tidak aktif; foto lain dimuat setelah `load`; tidak pindah ke foto yang belum termuat; Ken Burns setelah `load`; reduced motion (tanpa zoom/fade, +2 detik) | `x-data="hero(config)"`, hanya jika > 1 slide |
| `rotatingText` | Label masuk per karakter (naik, stagger 20ms), keluar ke atas; mengikuti `index` milik hero | `x-data="rotatingText(labels)"` |
| `blurText` | Memecah teks per kata: blur 10px + opacity 0 + 20px → normal, jeda 80ms, total ≤ 600ms | `x-data="blurText" data-blur-text` |
| `countUp` | Angka final ada di HTML; saat terlihat dihitung dari 0 dalam 2 detik (ease-out), format id-ID | `x-data="countUp({ to, decimals })" x-intersect.once="start()"` |
| `animatedContent` | Fade + naik 24px, 0,6 detik, sekali saat terlihat | `x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="0.2"` |
| `magnet` | Anak pertama mengikuti kursor maks 6px (rAF); nonaktif di layar sentuh dan saat reduced motion | `x-data="magnet"` pada pembungkus |

**ShinyText** murni CSS (`.shiny-text`): dasar putih 70%, kilau putih, 3 detik + jeda 2 detik.

**Menu mobile staggered** memakai CSS: dua lapisan navy lalu panel masuk dari kanan (jeda 70ms), kemudian item muncul satu per satu (stagger 60ms).

---

## 9. Implementasi SEO

- **HTML semantik:** `header`, `nav` (Main dan Mobile), `main#main`, `section` dengan `aria-labelledby`, `footer`. Satu `h1`, heading section `h2`. Semua navigasi memakai `<a href>` asli, ditambah skip link "Skip to content".
- **Meta:** `<html lang="en">`, title dari `seo_title`, meta description dari `seo_description` (fallback subteks hero), `robots: index, follow, max-image-preview:large`, canonical dari `APP_URL`.
- **Open Graph:** type, `og:locale en_US`, site_name, title, description, url, serta image dengan width/height/alt.
- **Twitter:** `summary_large_image`.
- **JSON-LD** (`App\Support\StructuredData`), satu `@graph`:
  - `Organization`: nama, url, logo, deskripsi, telepon, email, sameAs, areaServed.
  - `WebSite` dan `WebPage`.
  - `ItemList` berisi `Service` dari tabel services (provider → Organization).
  - Metode `locations()` sudah disiapkan untuk node `LocalBusiness` per lokasi yang merujuk Organization lewat `@id`.
- **robots.txt, sitemap.xml, llms.txt** dinamis (lihat bagian 7). Crawler AI tidak diblokir.
- **Preload:** foto hero pertama dengan `imagesrcset`/`imagesizes` yang sama persis dengan `<img>`, `fetchpriority="high"`, plus preload font 400 dan 800.

---

## 10. Hasil Fase 2: kesesuaian dengan rencana desain

| No | Poin | Status | Temuan | Tindakan |
|---|---|---|---|---|
| 1 | Tanpa 3D, efek kursor, scroll-jacking | ⚠️ Sebagian | Tidak ada 3D maupun scroll-jacking (scroll native + `scroll-padding`). **Magnet** pada CTA hero adalah efek kursor ringan (maks 6px), diminta di Fase 1 tetapi bertentangan dengan poin ini. | Dipertahankan sesuai Fase 1. **Perlu keputusan Anda.** |
| 2 | Accent #F26A21 hanya di CTA utama | ✅ | Pemakaian: token `--color-accent`/`--color-accent-hover` di `app.css`; `bg-accent`/`hover:bg-accent-hover` di varian `primary` `ui/button` (CTA navbar desktop, CTA hero, CTA menu mobile); `shadow-accent/30` pada CTA hero. | Menu aktif memakai garis ice dan teks putih, bukan oranye. |
| 3 | Tidak ada hex di luar token | ✅ | Overlay, bayangan, dan kilau diganti ke token/`color-mix`. Satu-satunya hex di luar CSS: `config/site.php` `theme_color` (#0F2A4A, cermin token navy untuk `<meta name="theme-color">`). | — |
| 4 | Tipografi Plus Jakarta Sans, heading 800 | ✅ | h1 dan h2 `font-extrabold` (800), font self-hosted. | — |
| 5 | Menu tepat 6 item | ✅ | Services, Locations, About Us, Clients, Insights, Careers. "Contact Us"/"Omnichannel" bukan item menu (Omnichannel hanya di dalam mega menu). | — |
| 6 | Kanan navbar: "ID \| EN" lalu "Get a Quote" | ✅ | Sesuai. Di mobile CTA hanya ada di panel menu. | Diperbaiki: CTA sempat bocor ke header mobile. |
| 7 | Hero tanpa panah/nomor, latar tidak berganti warna | ✅ | Hanya foto yang berganti; latar navy tetap. | — |
| 8 | Hierarki: headline lalu CTA | ✅ | Headline 56px/800; CTA oranye satu-satunya warna kuat. | — |
| 9 | CTA "Get a Quote" dan "Explore Services" | ✅ | — | — |
| 10 | Indikator berlabel nama layanan | ✅ | Desktop: label + garis progress. Mobile: garis saja (sesuai spesifikasi). | — |
| 11 | Subteks WCAG AA di keempat foto | ✅ | Diukur pada piksel latar **paling terang** di belakang subteks: minimal **5,18:1** (mobile/tablet) dan 7,22:1 (desktop). Semula 2,4:1 karena foto terang. | Overlay kiri berbasis kolom teks (`.hero-overlay-left`) untuk lg+, tint 70% di bawah lg. |
| 12 | Tanpa background animasi di hero | ✅ | Hanya Ken Burns pada foto (diminta spesifikasi); tidak ada gradien/partikel bergerak. | — |
| 13 | Tombol WhatsApp melayang belum dibuat | ✅ | Hanya tombol di menu mobile (disembunyikan jika nomor kosong). | — |
| 14 | `seo_title` default | ✅ | "Ekasa Cold Chain \| Cold Chain Logistics in Indonesia" (ditulis ulang, lihat bagian 14c). | — |
| 15 | Statistik seeder ditandai DUMMY | ✅ | Komentar di `HeroStatSeeder`. | — |

Kontras tambahan:

| Kombinasi | Rasio | Status |
|---|---|---|
| Teks CTA `#0A1D33` di `#F26A21` | 5,55:1 | Lulus AA; sebelumnya putih 3,06:1 |
| Teks CTA di warna hover `#F47C3C` | 6,30:1 | Lulus AA |

---

## 11. Hasil Fase 3: audit teknis

| No | Pemeriksaan | Status | Catatan |
|---|---|---|---|
| 1 | PHP 8.3, Laravel ^13 | ✅ | 8.3.35, laravel/framework 13.33.0 |
| 2 | package.json tanpa vue/inertia/react/gsap/motion/three | ✅ | Hanya alpinejs, @alpinejs/intersect, tailwindcss, @tailwindcss/vite, vite, laravel-vite-plugin, playwright-core (dev) |
| 3 | `migrate:fresh --seed` bersih | ✅ | — |
| 4 | `npm run build` tanpa error/warning | ✅ | Catatan informatif "plugin timings" dimatikan di `vite.config.js` |
| 5 | Pint | ✅ | `pint --test` lulus |
| 6 | PHPStan (larastan) | ✅ | 0 error (sebelumnya 9, semua diperbaiki di sumbernya) |
| 7 | Tanpa console.log/dd/dump, kode dikomentari, kredensial/URL lokal | ✅ | `console.log` hanya di skrip CLI audit (sengaja, mencetak hasil) |
| 8 | `.env.example` lengkap | ✅ | APP_*, DB (SQLite + MySQL), MEDIA_DISK, QUEUE sync, IMAGE_CONVERSIONS, WHATSAPP_NUMBER, ADMIN_* |
| 9 | Login admin dari .env | ✅ | Tes Pest |
| 10 | Settings (Umum/Hero/Mega Menu/SEO) tersimpan, badge WebP benar | ✅ | Tes Pest |
| 11 | CRUD, toggle, drag-and-drop | ✅ | Halaman create/list dirender (tes); reorder + toggle diuji lewat Livewire |
| 12 | Validasi upload jpg/png/webp maks 5 MB | ✅ | Tes: GIF dan file 6 MB ditolak |
| 13 | Upload tanpa GD | ✅ | Tes `IMAGE_CONVERSIONS=off` lulus. Tes upload dengan `on` di-skip karena GD tidak ada; tes registrasi konversi `on` lulus |
| 14 | Cache terhapus otomatis | ✅ | Model events, event media, `afterReordering`, simpan Settings |
| 15 | View-source memuat navbar, hero, meta, JSON-LD, preload | ✅ | JSON-LD valid (4 node) |
| 16 | Tanpa JavaScript | ✅ | Header solid navy, foto & label slide 1, statistik final, mega menu via hover/focus, menu mobile via `:target`, indikator disembunyikan (screenshot `hero-no-js-1440`) |
| 17 | Perilaku navbar & hero | ✅ | 4 slide sinkron foto+label; navbar 68px & solid setelah scroll; scrollspy benar (teks putih, garis ice); mega menu hover/klik/ArrowDown/Esc; menu mobile fokus, body lock, Esc |
| 18 | 1 slide / 0 slide | ✅ | Tes: 1 slide statis tanpa indikator; 0 slide latar navy tanpa error |
| 19 | Reduced motion | ✅ | Tanpa pemecahan kata, tanpa zoom, angka final, durasi slide 7 detik, semua konten langsung tampil |
| 20 | Keyboard, focus ring, aria-expanded | ✅ | Urutan tab logis dengan outline ice; `aria-expanded` pada Services dan hamburger |
| 21 | Responsif 375, 390, 768, 1024, 1280, 1366x650, 1440, 1920 | ✅ | Tanpa scroll horizontal, tanpa teks terpotong; tinggi hero = tinggi viewport di semua ukuran (varian `short`/`shorter` untuk laptop pendek) |
| 22 | Error console | ✅ | 0 error di semua ukuran |

### Lighthouse mobile (lokal, `php artisan serve`, hasil build)

| Kategori | Skor | Target |
|---|---|---|
| Performance | **37** | ≥ 95 (belum tercapai di lokal) |
| Accessibility | **100** | ≥ 95 ✅ |
| Best Practices | **100** | ✅ |
| SEO | **100** | 100 ✅ |

| Metrik | Nilai |
|---|---|
| FCP | 1,9 s |
| LCP | 129 s |
| CLS | **0,002** (target < 0,05 ✅) |
| TBT | 3.890 ms |
| Total transfer | 24,6 MB |

**Kenapa Performance rendah di lokal:**
- Foto hero original 3–10 MB (hingga 7491 px) dikirim tanpa WebP dan tanpa resize, lalu di-decode di bawah throttling CPU 4× dan jaringan "Slow 4G" simulasi Lighthouse.
- Server `php artisan serve` tidak memakai gzip maupun header cache.

**Target Performance ≥ 90 diukur ulang di hosting** setelah `php artisan media-library:regenerate` (WebP 800/1920 px, kualitas 78) dan dengan `.htaccess` aktif (gzip + cache).

### Ukuran bundle halaman depan

| File | Mentah | Gzip |
|---|---|---|
| JS (`app-*.js`, Alpine + intersect + komponen) | 64,9 KB | **22,4 KB** (target < 50 KB ✅) |
| CSS (`app-*.css`) | 41,7 KB | **8,4 KB** |
| Font | 12 KB per bobot | 400 dan 800 di-preload |

CSS Filament tidak dimuat di halaman publik.

### Tes otomatis (Pest)

**29 tes: 28 lulus, 1 di-skip, 117 assertion** (run 24 September 2026). Tes yang di-skip adalah upload dengan konversi `on`, yang butuh GD. Daftar tes:
- "/" 200 dengan title, meta description, dan headline di HTML mentah
- navbar, mega menu, statistik final, dan satu h1 ada di HTML
- preload = srcset/sizes gambar
- JSON-LD valid
- hanya slide/layanan aktif, sesuai urutan
- perubahan admin langsung tampil
- 1 slide statis; 0 slide tanpa error
- fallback file original saat konversi off
- robots.txt, sitemap.xml, llms.txt
- /admin mengarahkan tamu ke login
- admin seeder dari .env (dan dilewati jika kosong)
- semua halaman admin dirender
- badge WebP
- simpan Settings termasuk SEO
- reorder + toggle layanan
- upload dengan konversi off (dan on bila GD tersedia)
- registrasi konversi saat `on`
- tolak GIF; tolak file > 5 MB

### Screenshot (`docs/screenshots/tahap-1-2/`)

- `hero-desktop-1440.png`
- `hero-slide-1.png`, `hero-slide-2.png`, `hero-slide-3.png`, `hero-slide-4.png`
- `navbar-scrolled-1440.png`
- `mega-menu-open-1440.png`
- `hero-mobile-390.png`
- `mobile-menu-open-390.png`
- `hero-no-js-1440.png`

Dibuat dengan `node scripts/browser-check.mjs` (Chrome headless, animasi tidak di-throttle).

### Gambar yang dipakai

| File | Dimensi | Ukuran | Sumber |
|---|---|---|---|
| hero-cold-storage.jpg | 7491 x 5618 | 10.063 KB | Disediakan pemilik proyek, **PERLU DICEK** |
| hero-linehaul.jpg | 5900 x 3933 | 4.365 KB | Disediakan pemilik proyek, **PERLU DICEK** |
| hero-hyperlocal.jpg | 7008 x 4672 | 6.866 KB | Disediakan pemilik proyek, **PERLU DICEK** |
| hero-partnership.jpg | 4608 x 3456 | 3.064 KB | Disediakan pemilik proyek, **PERLU DICEK** |
| menu-services.jpg | 6240 x 4160 | 5.336 KB | Disediakan pemilik proyek, **PERLU DICEK** |
| logo-placeholder-white.svg / -navy.svg | 384 x 31 | 4,6 KB | Placeholder teks "PT Ekasa Cold Chain" |
| favicon-placeholder.png | 256 x 256 | 1 KB | Placeholder "E" di kotak navy |

Detail ada di `docs/image-credits.md`. Logo dan favicon masih placeholder sampai logo asli diunggah.

---

## 12. File yang dihapus, dibuat, dan diubah

**Dihapus**
- Seluruh `resources/js/` lama: Vue, TS, Vue Bits, hasil generate Wayfinder, layout dan halaman starter kit.
- `resources/views/app.blade.php`, `site.blade.php`, `resources/css/site.css`.
- `app/Actions/Fortify`, `app/Concerns/*ValidationRules`, `app/Http/Controllers/Settings`, `app/Http/Requests/Settings`, `app/Http/Middleware/{HandleAppearance,HandleInertiaRequests}.php`, `app/Providers/FortifyServiceProvider.php`.
- `routes/settings.php`, `config/{fortify,inertia}.php`, `components.json`, `tsconfig.json`, `chisel*.php`, `pnpm-workspace.yaml`, `vite.config.ts`, `.prettierrc`, `.prettierignore`.
- `public/{robots.txt,favicon.svg,apple-touch-icon.png,fonts-manifest.dev.json}`, `bootstrap/ssr/`.
- Tes starter kit (Auth, Settings, Dashboard, Example) dan tes PHPUnit lama.
- Paket: inertiajs/inertia-laravel, laravel/fortify, laravel/wayfinder, laravel/chisel, dan semua paket npm Vue/Inertia/motion/reka-ui/vite-plus/TypeScript.

**Dibuat**
- `app/Http/Controllers/SeoController.php`, `app/Http/Middleware/EnforceCanonicalHost.php`.
- `app/Support/{Seo,StructuredData,Icon}.php`.
- Migrasi `add_seo_and_contact_to_site_settings_table`.
- `config/navigation.php`.
- `resources/css/app.css` (baru), `resources/fonts/*.woff2`, `resources/svg/lucide/*.svg`, `resources/js/app.js`, `resources/js/alpine/*.js`.
- `resources/views/layouts/app.blade.php`, `resources/views/home.blade.php`, `resources/views/components/{layout,sections,ui}/*`, `resources/views/seo/*`.
- `vite.config.js`, `scripts/*.mjs`, `tests/Pest.php`, `tests/Feature/{HomePage,SeoFiles,AdminPanel,MediaUpload}Test.php`.
- `docs/*`, `public/build/*`.

**Diubah**
- `bootstrap/{app,providers}.php`, `composer.json` (skrip), `package.json`, `.gitignore` (`public/build` di-commit).
- `.env.example` (SSR dihapus, WHATSAPP_NUMBER, QUEUE sync), `phpunit.xml`, `public/.htaccess`.
- `app/Models/{SiteSetting,User}.php`, `app/Models/Concerns/RegistersWebpConversions.php`.
- `app/Support/{FrontendData,MediaPresenter,PlaceholderImage}.php`, `app/Providers/AppServiceProvider.php`, `app/Http/Controllers/HomeController.php`, `app/Filament/Pages/ManageSiteSettings.php`.
- `config/{site,media-library}.php`, `database/seeders/{SiteSetting,AdminUser}Seeder.php`, `database/seeders/Concerns/SeedsImages.php`, `database/factories/UserFactory.php`, `tests/TestCase.php`, `README.md`.

---

## 13. Penyimpangan dari spesifikasi

1. **Headline tidak dibungkus animatedContent.** Animasinya sepenuhnya dari blurText: 5 kata × 80ms + 280ms = 600ms. Dengan begitu batas "animasi headline maks 600ms" terpenuhi; jika dibungkus lagi, totalnya menjadi 1,3 detik.
2. **Gradien overlay kiri** tidak berhenti tepat di 65% lebar. Nilainya 90% → 80% sampai akhir kolom teks (min. 680px) lalu transparan di max(70%, 1000px). Di bawah lg dipakai tint rata 70%. Alasannya kontras AA subteks di foto yang sangat terang.
3. **countUp** memakai teks `sr-only` berisi angka final plus `aria-hidden` pada angka yang berjalan, bukan `aria-label`, karena `aria-label` pada elemen generik diabaikan banyak pembaca layar. Hasilnya sama: pembaca layar hanya membaca angka final.
4. **Hover tombol accent** dibuat sedikit lebih terang (#F47C3C), bukan lebih gelap, supaya teks navy tetap kontras (6,3:1).
5. **Footer minimal** ditambahkan demi HTML semantik (`footer`); footer lengkap untuk tahap berikutnya.
6. **Redirect www/non-www** dilakukan di middleware `EnforceCanonicalHost` (mengikuti `APP_URL`), karena `.htaccess` tidak bisa membaca `.env`. `.htaccess` hanya memaksa HTTPS.
7. **Mega menu tanpa JS** muncul lewat `:hover`/`:focus-within`, dan **menu mobile tanpa JS** lewat `:target`, supaya semua link tetap bisa diklik.
8. **Foto slide 2–4 tidak ada di HTML awal.** Foto dibuat oleh JS setelah `window.load`, sesuai aturan performa. Untuk crawler, foto pertama dan semua label ada di HTML.

---

## 14. Masalah yang diketahui / belum selesai

1. **Foto seeder berukuran original (3–10 MB)** dan sebagian tidak cocok dengan kriteria visual. Rinciannya: foto udara salju, truk hitam-putih, pekerja berbaju oranye dengan merek di kardus, dan perahu bersalju untuk mega menu. Foto dipakai sesuai keputusan pemilik proyek. Performa hanya membaik setelah WebP aktif di server.
2. **Logo** masih placeholder teks (versi putih dan navy). Logo navy dipakai di JSON-LD.
3. **Konversi WebP belum bisa diverifikasi di lokal** (GD diblokir), dan tes upload dengan `on` di-skip.
4. **Pemeriksaan otomatis "mega menu menutup saat kursor keluar"** sekali gagal dalam run penuh, tetapi lulus 3/3 pada uji terisolasi dan pada run sebelumnya. Kemungkinan flaky di urutan skrip; perlu dicek manual.
5. **Kontras AA subteks** diukur dengan foto saat ini. Jika klien mengunggah foto yang lebih terang lagi, jalankan ulang `node scripts/contrast-check.mjs`.
6. **Nomor WhatsApp, email, telepon, Instagram, dan LinkedIn masih kosong**, sehingga tombol WhatsApp di menu mobile disembunyikan dan JSON-LD belum memuat kontak.
7. **Logo asli belum ada.** Placeholder teks "PT Ekasa Cold Chain" dipakai sampai logo asli diunggah lewat **Pengaturan Situs › Umum**.
8. **Domain produksi belum ditetapkan.** Isi `APP_URL` dan `ADMIN_EMAIL` di `.env` hosting dengan domain Ekasa Cold Chain.

---

## 14a. Pembaruan 24 September 2026

1. **Nama perusahaan diganti menjadi Ekasa Cold Chain.** Yang berubah: pengaturan situs (nama, judul SEO, deskripsi SEO) di database dan seeder, nama panel admin ("Ekasa Cold Chain Admin"), judul section "About Ekasa Cold Chain", dan `APP_NAME` di `.env`. JSON-LD, Open Graph, dan `llms.txt` ikut berubah karena mengambil nama dari pengaturan situs. Admin yang sedang login perlu login ulang sekali.
2. **Loading bar indikator hero sering berhenti di tengah.** Penyebabnya: pause saat hover terpasang di seluruh kolom teks hero (judul, subteks, CTA, indikator), sehingga bar berhenti setiap kali kursor berada atau lewat di area itu, dan sentuhan di HP juga ikut memicu pause. Perbaikan di `resources/views/components/sections/hero.blade.php`: pause hanya saat mouse berada di atas baris indikator, sentuhan tidak memicu pause, dan setelah indikator diklik bar slide baru langsung berjalan.
3. **Semua teks situs bisa diubah dari admin.** `config/navigation.php` dihapus. Menu baru **Konten Beranda › Section & Menu** (tabel `page_sections`) mengatur label menu navbar, tampil/tidaknya di menu, eyebrow, judul, deskripsi, warna latar, urutan, dan status aktif tiap section. ID section (anchor) tetap di kode, dan section tidak bisa ditambah atau dihapus karena tata letaknya dibangun per tahap. Pengaturan Situs mendapat field deskripsi perusahaan dan wilayah layanan, label tombol kedua di hero, serta tab baru **Teks & Label** (tombol WhatsApp, teks hak cipta, dan label pembaca layar). Nama panel admin mengikuti nama perusahaan. Yang masih di kode hanya ID section, kode bahasa "ID | EN", dan bahasa halaman (`lang="en"`).
4. **Warna bisa diubah dengan kode hex.** Tab **Pengaturan Situs › Warna** berisi 9 warna (navy, navy gelap, navy terang, ice, accent, accent hover, latar abu-abu muda, teks utama, teks sekunder). Nilainya disimpan di kolom JSON `colors`, divalidasi `#RRGGBB`, lalu dicetak sebagai variabel CSS `:root{--color-*}` setelah `app.css`, sehingga tanpa build ulang. `theme-color` mengikuti navy (`config('site.theme_color')` dihapus). Setelah disimpan, `App\Support\ColorContrast` memeriksa 9 pasangan teks/latar dan menampilkan peringatan bila di bawah 4,5:1. Warna bawaan lolos semua pasangan (ada tesnya). Kontras subteks hero di atas foto tidak ikut dicek otomatis.

---

## 14b. Revisi layar pertama (navbar, mega menu, hero)

Satu commit per bagian (`git log --oneline`): overlay, tipografi, trust row, kartu tracker, bar statistik, gerak, navbar & mega menu. Screenshot kondisi sekarang: `docs/screenshots/layar-pertama/` (dibuat dengan `node scripts/screen-shots.mjs <folder>`). Screenshot "sebelum" dihapus karena memuat logo situs referensi desain.

### Perubahan

| Bagian | Perubahan |
|---|---|
| 1. Overlay | Penyebab garis batas di ~50% lebar: overlay kiri ditahan 90% lalu turun ke 0 dalam 200 px. Diganti scrim 9 titik (0,90 → 0) yang lebarnya mengikuti kolom teks (ujung subteks di 40% gradasi), lapisan navy-dark 18% di seluruh foto, gradasi bawah bertitik (0,85 → 0 di ~35%). Tanpa blur pada foto. |
| 2. Tipografi | Headline `clamp` 36 → 64 px, line-height 1,05, tracking -0,02em, `text-wrap: balance` di baris statis. Tinggi area label = label terpanjang pada lebar layar saat itu; ukuran label mengecil hanya jika label terpanjang tidak muat satu baris (375 px: 164 → 109 px, tanpa baris kosong). Layar < 700 px tinggi: headline 42 px dan jarak dirapatkan. Indikator: tidak aktif 70%, hover 90%, aktif 100%. |
| 3. Trust row | "Trusted by" + maks. 5 logo (tinggi 24 px, opacity 70%, dibuat putih seragam). Tidak dirender jika kosong. Desktop: boleh lebih lebar dari kolom teks (maks. 880 px, menyisakan 320 px untuk kartu tracker); layar sempit: geser horizontal tanpa scrollbar. Seeder kosong. |
| 4. Kartu tracker | Desktop saja, aria-hidden. Label ruangan, status "Live" (titik hijau 2 detik), suhu, sparkline 24 titik. Nilai awal dirender server (tampil statis tanpa JS / reduced motion); dengan JS berubah dalam rentang admin tiap 3 detik, berhenti saat tab tidak aktif atau hero tidak terlihat. Muncul 400 ms setelah konten hero. |
| 5. Bar statistik | Kolom sejajar container, padding dalam seragam di semua kolom, ikon Lucide 20 px (ice) di atas angka, latar navy-dark 55% + blur 12 px, garis atas putih 10%. |
| 6. Gerak | Parallax foto 0,1x (desktop, `transform`, hanya saat hero terlihat, nonaktif saat reduced motion). Crossfade, Ken Burns, dan penundaan foto slide 2–4 tetap. Tidak ada teks hero yang dimulai dari opacity 0 (min. 0,01). Tanpa efek kursor. |
| 7. Navbar & mega menu | Scrim atas bertitik (0,82 → 0 dalam 176 px) agar "ID" (60%) lulus AA. Sisanya diverifikasi: CTA outline → oranye setelah scroll > 40 px (250 ms), mega menu 2x2 + pemisah + Omnichannel, overlay 40%, panel 16 px, ikon ice 10% / hover ice solid. |

Perbaikan bug yang ditemukan: di komponen Alpine bersarang, properti yang tidak dideklarasikan ditulis ke scope hero, sehingga timer `rotatingText` menimpa interval tracker (tracker tidak berhenti saat hero tidak terlihat). `tracker`, `rotatingText`, dan `countUp` kini mendeklarasikan semua propertinya.

Verifikasi ulang (24-09-2026): `backdrop-blur-sm` pada badge hero dihapus agar blur hanya ada di kartu tracker dan bar statistik sesuai spesifikasi. Tes, Pint, PHPStan, build, `browser-check`, `contrast-check`, dan `hero-overlay-check` lulus ulang; angka di bawah tetap berlaku.

**Pembaruan 24-09-2026:** kartu realtime tracker dihapus seluruhnya (komponen, Alpine, CSS, field admin, kolom `tracker_*` lewat migrasi `drop_tracker_from_site_settings_table`). Lighthouse mobile ulang (3 run: 80/80/80, TBT 60/80/60 ms, median TBT 60 ms, CLS 0-0,001): TBT sudah di bawah angka sebelum revisi (70 ms), jadi parallax dipertahankan (hanya aktif di desktop, tidak berjalan di pengukuran mobile). Identitas: nama perusahaan, kontak, dan domain di kode sudah diambil dari Pengaturan Situs dan `APP_URL`; tes baru memastikan halaman publik, llms.txt, sitemap, dan robots.txt tidak memuat nama lama saat pengaturan diganti.

### Field admin baru

| Tempat | Field |
|---|---|
| Konten Beranda › **Logo Klien** (baru, tabel `trust_logos`) | Nama klien, logo (SVG/PNG, maks. 1 MB, sisi maks. 4000 px), aktif, urutan (drag and drop) |
| Pengaturan Situs › Hero › Kartu realtime tracker | Tampilkan kartu, label ruangan, suhu terendah, suhu tertinggi (divalidasi min ≤ max), satuan, teks status ("Live") |
| Pengaturan Situs › Teks & Label | Teks sebelum logo klien ("Trusted by") |
| Konten Beranda › Statistik Hero | Ikon (Lucide, boleh kosong; kolom `hero_stats.icon`) |

### Hasil pengukuran

- **Kontras subteks** (keempat foto, foto putih polos, foto terang; 390–1920 px): min. 5,12:1. **"ID" 60% di navbar:** min. 4,92:1 (foto putih polos), tautan menu min. 7,96:1. Lompatan kecerahan overlay maks. 1,9 per 4 px (tanpa garis batas).
- **Responsif** 375, 390, 768, 1024, 1280, 1366x650, 1440, 1920: tanpa scroll horizontal, tanpa teks terpotong, hero = satu layar di semua ukuran (juga dengan 5 logo uji sementara di 1366x650). 0 error console, 0 respons ≥ 400.
- **Tanpa JavaScript:** teks, statistik final, foto pertama, dan kartu tracker (statis) tampil; trust row dirender server.
- **Ukuran:** JS 23,2 KB gzip, CSS 9,4 KB gzip.
- **Tes:** 53 (52 lulus, 1 di-skip karena GD), Pint dan PHPStan bersih.
- **Lighthouse mobile** (Lighthouse 12, build lokal di `php artisan serve`, median 3 run):

| Metrik | Sebelum revisi | Sesudah revisi |
|---|---|---|
| Performance | 80 | 75 (80 / 71 / 75) |
| LCP | 4,9 s | 4,9 s (run 2: 8,8 s, server lokal lambat) |
| Elemen LCP | Subteks hero | Subteks hero |
| FCP | 2,0 s | 2,0 s |
| TBT | 70 ms | 130 ms |
| CLS | 0,006 | 0 |
| A11y / BP / SEO | 100 / 100 / 100 | 100 / 100 / 100 |

LCP simulasi masih didominasi foto hero pertama (JPG 455 KiB, karena WebP belum bisa dibuat tanpa GD di lokal); LCP yang teramati di browser sama dengan FCP (1,1–1,7 s). TBT naik sedikit karena JS kartu tracker dan parallax.

---

## 14c. Identitas PT Ekasa Cold Chain

coldspace.id hanya referensi desain. Materi pihak lain sudah dibersihkan:

- **Logo dan favicon coldspace.id dihapus** dari `database/seeders/images/`, storage, dan media library (`migrate:fresh --seed`). Repository dimulai ulang dengan satu commit dari kondisi bersih; riwayat lama hanya disimpan sebagai cadangan lokal di luar proyek.
- **Placeholder:** teks "PT Ekasa Cold Chain" (Plus Jakarta Sans 800 sebagai path SVG, putih dan navy, tanpa simbol) dan favicon "E" putih di kotak navy #0F2A4A (`favicon-placeholder.png` dan `public/favicon.ico`).
- **Field baru `legal_name`** (Pengaturan Situs › Umum › Nama resmi): dipakai di hak cipta footer dan `legalName` JSON-LD, jika kosong dipakai nama merek. Seeder: "Ekasa Cold Chain" / "PT Ekasa Cold Chain".
- **SEO seeder ditulis baru:** "Ekasa Cold Chain | Cold Chain Logistics in Indonesia" dan deskripsi baru.
- **DUMMY - harus dikonfirmasi klien:** statistik hero (termasuk rentang suhu) dan rentang suhu di deskripsi Cold Storage. Belum ada data sertifikasi di proyek. `trust_logos` kosong.
- **Foto:** sumber dan lisensi kelima foto tidak diketahui (metadata kosong), ditandai **PERLU DICEK** di `docs/image-credits.md`.
- Default kolom lama (`company_name`, `seo_title`) di migrasi awal diganti "Company Name"; `.env.example` memakai `APP_NAME="Ekasa Cold Chain"`.
- Tes: halaman publik, sitemap, robots.txt, dan llms.txt tidak memuat "coldspace".

---

## 14d. Dua bahasa: English (utama) dan Bahasa Indonesia

Tanpa package tambahan (Laravel 13.33, Filament 5.8.4, Livewire 4.4.6).

- **URL:** English di `/`, Indonesia di `/id`. Bahasa hanya dari URL (middleware `SetLocale`), tanpa redirect berdasarkan browser/IP. `/admin`, `robots.txt`, `sitemap.xml`, `llms.txt` tanpa prefix. Daftar bahasa di `config/site.php` (`locales`).
- **Tombol "ID | EN":** link biasa ke halaman yang sama di bahasa lain (anchor ikut dengan JS), bahasa aktif `aria-current`, `aria-label` "Baca dalam Bahasa Indonesia" / "Read in English", juga di menu mobile.
- **Teks antarmuka:** bawaan di `lang/en.json` dan `lang/id.json`; tetap bisa diubah per bahasa di Pengaturan Situs › Teks & Label (kolom kosong = bawaan bahasa itu). Angka mengikuti bahasa (1,250 / 1.250).
- **Konten admin:** field teks disimpan per bahasa (JSON `{"en","id"}`), form menampilkan EN dan ID berdampingan, tabel menampilkan badge "Versi ID". Versi ID kosong → tampil versi EN.
- **SEO:** `<html lang>`, `og:locale` + `og:locale:alternate`, canonical per bahasa, hreflang en/id/x-default, sitemap dengan `xhtml:link`, JSON-LD `inLanguage`. `llms.txt` tetap satu dan mencantumkan kedua URL.
- **Cache:** per bahasa, keduanya dihapus saat admin menyimpan.
- **Tata letak:** nilai statistik yang panjang (misalnya "-25°C hingga 6°C") mengecil hingga 20 px sebelum turun baris, sehingga hero tetap satu layar di semua ukuran desktop dalam kedua bahasa.

### Field admin yang berubah menjadi dua bahasa

| Tempat | Field |
|---|---|
| Pengaturan Situs | Deskripsi perusahaan, label CTA, badge, headline, subteks hero, judul dan label tautan kartu mega menu, judul dan meta description SEO, semua Teks & Label |
| Layanan | Judul, deskripsi singkat |
| Slide Hero | Label layanan, teks alternatif foto |
| Statistik Hero | Label, nilai teks |
| Section & Menu | Label menu, eyebrow, judul, deskripsi |

Tidak diterjemahkan: nama merek, nama resmi, kontak, wilayah layanan, nama klien di Logo Klien.

### Daftar istilah terjemahan (untuk direview)

| English | Indonesia | Catatan |
|---|---|---|
| Cold Storage | Cold Storage | Istilah industri, dipertahankan |
| Temperature-Controlled Logistics (badge) | Logistik Terkendali Suhu | Tagline orisinal. Di judul SEO dipakai "Logistik Rantai Dingin" agar cocok dengan kata kunci pencarian |
| Linehaul Transport | Transportasi Linehaul | "Linehaul" dipertahankan |
| Refrigerated and dry trucks | Truk berpendingin dan dry | "Dry" dipertahankan |
| Hyperlocal Fulfilment | Hyperlocal Fulfilment | Dipertahankan |
| Logistic Partnership | Kemitraan Logistik | |
| Omnichannel Integration | Integrasi Omnichannel | |
| Chilled and frozen rooms | Ruang chiller dan frozen | "Chiller" dan "frozen" dipertahankan |
| Supply chain | Rantai pasok | |
| Marketplace | Marketplace | |
| Get a Quote | Minta Penawaran | |
| Explore Services | Lihat Layanan | |
| Talk to our team | Hubungi tim kami | |
| Need a custom solution? | Butuh solusi khusus? | |
| Delivery Points / Warehouses / Temperature Range / Trusted Brands | Titik Pengiriman / Gudang / Rentang Suhu / Klien Terpercaya | |
| Services / Locations / About Us / Clients / Insights / Careers / Contact | Layanan / Lokasi / Tentang Kami / Klien / Wawasan / Karier / Kontak | |
| Trusted by | Dipercaya oleh | |
| All rights reserved. | Hak cipta dilindungi undang-undang. | |
| Skip to content | Langsung ke konten | |

### Hasil

- Tes: 69 lulus, 1 di-skip (GD). Pint dan PHPStan bersih. JS 22,7 KB gzip.
- Tampilan `/id` di 375, 390, 768, 1024, 1280, 1366x650, 1440, 1920: tanpa scroll horizontal, hero satu layar di desktop, navbar, tombol, indikator, dan mega menu tidak terpotong. Screenshot: `docs/screenshots/bilingual/`.
- Lighthouse mobile (median 3 run): `/` Performance 82, `/id` 82; A11y, Best Practices, SEO 100 di kedua bahasa; LCP 4,7 s, CLS ≤ 0,003, TBT 60 ms (`/`) dan 10 ms (`/id`).

---

## 14e. Bantuan draf terjemahan dengan review

- **Status per field ID:** Kosong, "Draf - perlu dicek", atau "Sudah dicek" (kolom `translation_status`). `/id` hanya menampilkan teks ID yang "Sudah dicek"; selain itu tampil versi EN. Teks ID yang sudah ada saat migrasi (ditulis manual) otomatis "Sudah dicek".
- **Tombol "Buat draf dari EN/ID"** di setiap field teks, hanya tampil jika `TRANSLATION_DRIVER` (`deepl` atau `google`) dan `TRANSLATION_API_KEY` diisi di `.env`. Hasilnya hanya draf (status "Draf - perlu dicek") dan belum tampil di situs.
- **Glosarium:** Pengaturan Situs › Terjemahan. Istilah tanpa terjemahan tetap tidak diterjemahkan; istilah dengan terjemahan tetap diganti. Seeder mengisi 13 istilah (tabel di atas).
- **Dasbor:** jumlah teks ID kosong dan draf yang perlu dicek.
- Teks & Label tidak memakai status review, karena kolom kosongnya memakai terjemahan bawaan dari `lang/id.json`.

---

## 14f. Optimasi Lighthouse dari sisi kode

| Poin | Hasil |
|---|---|
| 1. JavaScript | Halaman publik hanya memuat `app.js` (65 KB, 22,9 KB gzip): Alpine 56 KB + komponen situs ~9 KB. Tidak ada skrip Livewire/Filament (ada tesnya). "Unused JavaScript" (~36 KiB) hampir seluruhnya bagian Alpine yang belum dipanggil saat load. Dynamic import diuji untuk parallax, tetapi runtime chunk Vite membuat `app.js` lebih besar (65,0 → 66,3 KB), jadi tidak dipakai. |
| 2. Animasi | Shiny text kini hanya `transform` (pita sorot + salinan teks yang digeser berlawanan, digabung `plus-lighter`); posisi dan waktu sorot sama. Indikator progres sudah `scaleX`. Blur headline dipertahankan (5 kata tetap tercatat "non-composited" karena filter blur tidak bisa di-composite); `will-change` hanya selama animasi. Catatan: teks badge kini dirender sebagai teks biasa, bukan `background-clip: text`, sehingga ~6% lebih terang secara piksel pada warna yang sama (70% putih). |
| 3. Critical CSS | **Dinonaktifkan** (keputusan setelah pengukuran): halaman kembali memakai `<link rel="stylesheet">` biasa. Skrip `scripts/critical-css.mjs` disimpan untuk diuji ulang di hosting. Saat masih aktif: skrip membuat `public/build/critical.css` (aturan layar pertama di 7 ukuran layar dan 2 bahasa: 27,9 KB, **6,4 KB gzip**). Layout menyisipkannya inline dan memuat `app.css` tanpa blokir render; file diabaikan jika tidak cocok dengan build CSS. Dengan `app.css` ditunda 3 detik: perubahan piksel ≤ 0,02%, CLS ≤ 0,001. |
| 4. Long tasks | Section di bawah hero memulai komponen Alpine saat mendekati viewport (300 px) atau saat idle setelah load. |

**Pengukuran** (Lighthouse 12 mobile, median 5 run, di belakang proxy gzip lokal agar setara hosting; `php artisan serve` sendiri tidak mengompres):

| | Performance | FCP | LCP | TBT | Speed Index | CLS |
|---|---|---|---|---|---|---|
| Sebelum (commit `ce658ec`) | 75 | 1,23 s | 4,07 s | 87 ms | 2,95 s | 0,003 |
| Sesudah poin 1-4 | 86 | 1,36 s | 4,07 s | 21 ms | 2,16 s | 0,003 |
| Sesudah, tanpa critical CSS | 87 | 1,24 s | 4,07 s | 85 ms | 2,16 s | 0,002 |

Skor per run berganti antara ~75 dan ~87 (server lokal), jadi median Performance kurang stabil; TBT dan Speed Index lebih bisa dipegang. Critical CSS di lokal netral (FCP +0,1 s): HTML + CSS inline = 15,3 KB gzip, sedikit di atas jendela ~14 KB round trip pertama, dan di lokal CSS tidak benar-benar memblokir (audit render-blocking 0 ms). Karena tidak terbukti membantu, critical CSS dinonaktifkan; lihat catatan di `scripts/critical-css.mjs` untuk mengaktifkannya lagi jika PageSpeed Insights di hosting menunjukkan render-blocking CSS yang berarti.

**Temuan yang tersisa:**
- *Unused JavaScript ~36 KiB*: bagian Alpine yang tidak dipakai saat load; hanya bisa hilang dengan mengganti Alpine.
- *Non-composited animations (5)*: kata-kata blur headline; efek dipertahankan sesuai permintaan.
- *LCP 4,1 s* (elemen: subteks hero): didominasi foto hero JPG 455 KB yang bersaing bandwidth; beres dengan WebP di hosting (`media-library:regenerate`).
- *Server response time, text compression, cache lifetime, image delivery*: urusan hosting (diabaikan sesuai instruksi).

---

## 15. Hal yang perlu Anda cek manual

1. Tampilan dan animasi di browser nyata: blur headline, pergantian label per karakter, crossfade + Ken Burns, kilau badge, count-up, dan efek staggered menu mobile.
2. Hover mega menu dan jeda tutup 150ms dengan mouse sungguhan.
3. Magnet pada CTA hero sudah dihapus (tanpa efek kursor).
4. Pilihan foto untuk hero dan mega menu (lihat bagian 14 no. 1).
5. `craiyon_200122_image.png` sudah tidak ada di proyek.
6. Isi kontak dan media sosial di **Pengaturan Situs › Umum**.
7. Di hosting: skor Lighthouse setelah `media-library:regenerate`, redirect HTTPS dan www, serta header cache.

---

## 16. Cara menjalankan di lokal

**1. Aktifkan PHP 8.3** di setiap terminal baru. XAMPP PHP 8.2 ada di PATH Machine dan Smart App Control memblokir GD 8.3. Skrip ini menaruh `C:\Users\Acer\php83` di depan PATH, membuang XAMPP dari PATH sesi, dan mengosongkan `PHP_INI_SCAN_DIR` (folder global `~/.php-ini` memuat gd yang diblokir). Tidak ada pengaturan sistem yang diubah.

```powershell
. .\use-php83.ps1            # PowerShell
# atau: source ./use-php83.sh   (Git Bash)
```

**2. Instal dan siapkan database:**

```bash
composer install
npm install
cp .env.example .env         # isi ADMIN_EMAIL, ADMIN_PASSWORD, opsional WHATSAPP_NUMBER
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
```

**3. Build aset.** Pakai `npm run build` (hasil di `public/build`), atau `npm run dev` untuk pengembangan. Jika Vite dev pernah dijalankan lalu dihentikan paksa, hapus `public/hot`.

**4. Jalankan server:**

```bash
php artisan serve                # http://127.0.0.1:8000 dan /admin
```

**5. Pemeriksaan:**

```bash
vendor/bin/pest
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
node scripts/browser-check.mjs   # butuh Google Chrome terpasang
node scripts/contrast-check.mjs
```

---

## 17. Cara deploy ke shared hosting cPanel

1. **Pastikan PHP 8.3+** di cPanel (MultiPHP Manager), dengan ekstensi `gd` (dukungan WebP), `intl`, `fileinfo`, `exif`, `zip`, dan `pdo_mysql` aktif.
2. **Buat database MySQL** beserta user-nya di cPanel.
3. **Unggah proyek** ke folder di luar `public_html`, misalnya `~/ekasa`, termasuk `vendor/` jika Composer tidak tersedia di server, dan termasuk `public/build/` (sudah di-commit, tidak perlu Node).
4. **Atur document root:**
   - Arahkan domain ke `~/ekasa/public`.
   - Jika document root tidak bisa diubah: salin isi `public/` ke `public_html` dan ubah path di `public_html/index.php` (`__DIR__.'/../ekasa/...'`).
5. **Buat `.env`** dari `.env.example`:
   - `APP_ENV=production`, `APP_DEBUG=false`
   - `APP_URL=https://domain-ekasa.co.id` (atau `https://www.domain-ekasa.co.id`, versi yang ditulis di sini menjadi canonical)
   - `DB_CONNECTION=mysql` dan kredensial database
   - `QUEUE_CONNECTION=sync`, `IMAGE_CONVERSIONS=auto`
   - `ADMIN_EMAIL`, `ADMIN_PASSWORD` (kata sandi kuat), `WHATSAPP_NUMBER`
6. **Jalankan perintah via Terminal cPanel/SSH** di folder proyek:
   ```bash
   composer install --no-dev --optimize-autoloader   # jika vendor tidak diunggah
   php artisan key:generate                          # hanya pertama kali
   php artisan migrate --force --seed                # --seed hanya pada deploy pertama
   php artisan storage:link
   php artisan filament:assets
   php artisan media-library:regenerate              # buat semua versi WebP (1920/800, 1200/600)
   php artisan optimize
   ```
7. **Verifikasi:**
   - Halaman admin **Pengaturan Situs** menampilkan "Konversi WebP: Aktif (GD)".
   - `https://domain/robots.txt`, `/sitemap.xml`, dan `/llms.txt` merespons.
   - `http://` dan `www.` dialihkan ke URL canonical.
8. **Setiap update berikutnya:** build aset di lokal (`npm run build`), unggah file yang berubah (termasuk `public/build`), lalu jalankan `php artisan migrate --force` dan `php artisan optimize`.

---

## 18. Kesiapan tahap 3

Section placeholder (`services`, `locations`, `about`, `clients`, `insights`, `careers`, `contact`) sudah memakai komponen fondasi dan siap diisi.

```blade
<x-ui.section id="services" variant="surface" aria-labelledby="services-title">
    <x-ui.section-heading
        id="services-title"
        eyebrow="Services"
        title="Cold chain solutions end to end"
        description="Storage, transport and fulfilment under one team."
        align="center"            {{-- left | center --}}
    />

    <div class="mt-12 grid gap-6 md:grid-cols-3">
        {{-- kartu layanan --}}
    </div>

    <div class="mt-10 flex justify-center gap-4">
        <x-ui.button href="#contact" size="lg">Get a Quote</x-ui.button>
        <x-ui.button href="#locations" variant="secondary" tone="light" icon="arrow-right" class="group">
            See Locations
        </x-ui.button>
    </div>
</x-ui.section>

<x-ui.section id="contact" variant="navy">
    <x-ui.section-heading title="Talk to our team" tone="dark" />
</x-ui.section>
```

**Aturan pemakaian:**
- `ui/section`: jarak 64px (mobile) / 96px (desktop); varian `white`, `surface`, `navy`; `:contained="false"` untuk konten selebar layar.
- `ui/section-heading`: sudah beranimasi (fade + naik 24px). Gunakan `tone="dark"` di latar navy.
- `ui/button`: `primary` hanya untuk CTA utama (accent); `secondary` untuk outline; `ghost` untuk aksi ringan. `tone` menyesuaikan latar gelap atau terang.
- `ui/container`: pembungkus lebar 1280px untuk konten di luar `ui/section`.
- **Animasi elemen lain:** tambahkan `x-data="animatedContent" x-intersect.once="show()" data-animate data-delay="0.1"`.
- **JSON-LD LocalBusiness:** isi `StructuredData::locations()` saat tabel lokasi dibuat.

---

## 19. Penilaian jujur

**Masih kurang:**
- **Foto adalah kelemahan terbesar.** Nuansanya tidak seragam (salju terang, hitam-putih, oranye hangat) dan sebagian tidak relevan dengan layanan (perahu bersalju di mega menu). Karena foto terang, overlay harus cukup gelap, sehingga hero terasa berat dan kurang "dingin-bersih" seperti yang diinginkan. Satu set foto yang senada akan memberi dampak visual paling besar.
- **Performa di lokal belum bisa membuktikan target Lighthouse.** Semua fondasinya sudah benar (preload, fetchpriority, srcset, JS 22 KB, CLS 0,002), tetapi angka akhirnya bergantung pada WebP di server.
- **Animasi belum saya lihat bergerak di browser nyata.** Semua status dan urutan kelas terverifikasi lewat Chrome headless dan screenshot, tetapi kesan kehalusannya (easing, tempo 20ms per karakter, efek staggered menu) perlu penilaian mata manusia.
- **Hero di laptop pendek (1366x650)** memang pas satu layar, tetapi headline mengecil ke 42px dan jaraknya rapat. Di layar seperti itu hero terasa padat.
- **Magnet** sudah dihapus (tanpa efek kursor).
- **Teknis:**
  - Pengaman 3 detik untuk kelas `js`/`js-anim` bergantung pada `window.Alpine`. Jika bundle terlambat lebih dari 3 detik, animasi masuk dilewati (konten tetap tampil).
  - Konten dummy section masih berupa placeholder.

**Sudah kuat:**
- Keterbacaan tanpa JavaScript, struktur SEO/JSON-LD, dan aksesibilitas (Lighthouse A11y 100, kontras terukur ≥ 5,18:1).
- Tidak ada layout shift.
- Panel admin yang lengkap, dengan cache yang otomatis tersinkron.
