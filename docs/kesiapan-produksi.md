# Kesiapan produksi

Ringkasan status kesiapan. Rincian teknis: `SECURITY_AUDIT.md` (temuan dan perbaikan), `DEPLOY.md` (langkah server), `SECURITY_CHECKLIST_DEPLOY.md` (hal manual).

## Data pribadi pada Pesan Masuk (form Kontak): retensi

Tabel `contact_messages` menyimpan data pribadi pengunjung:

| Kolom | Isi | Ditampilkan di |
|---|---|---|
| `name`, `email`, `phone`, `company`, `message` | diisi pengunjung | admin (tabel & detail, hanya administrator), email notifikasi |
| `ip`, `user_agent` | dicatat server saat submit (untuk rate limit dan penelusuran spam) | **hanya** halaman detail di admin; tidak pernah tampil di situs publik |

**Kebijakan yang berlaku (diimplementasikan):**

- `php artisan leads:prune` dijalankan **harian 03:15** oleh scheduler (`bootstrap/app.php`, `withSchedule`).
- **60 hari**: `ip` dan `user_agent` dikosongkan (hanya berguna untuk melawan spam; nama, email, pesan tetap ada).
- **12 bulan**: seluruh pesan dihapus.
- Angka bisa diubah lewat `.env` (`LEADS_ANONYMIZE_AFTER_DAYS`, `LEADS_DELETE_AFTER_MONTHS`; `0` mematikan langkah itu).
- Syarat: satu entri cron `schedule:run` di server (DEPLOY.md A13). Tanpa cron tidak ada yang dibersihkan.
- Uji tanpa mengubah data: `php artisan leads:prune --dry-run`. Setiap pembersihan tercatat ringkas di `security.log` (`leads.pruned`, jumlah saja).
- Akses ke data ini dibatasi ke administrator (`ContactMessagePolicy`); menghapus satu pesan dari admin tercatat (`lead.deleted`, id saja).

**Masih perlu keputusan/tindakan Anda bila kepatuhan privasi dibutuhkan (UU PDP Indonesia, atau GDPR untuk pengunjung luar negeri):**

- Pemberitahuan privasi dekat form ("data Anda dipakai untuk membalas permintaan ini") dan halaman kebijakan privasi (belum ada).
- Permintaan subjek data (mis. hapus semua pesan dari satu alamat email): saat ini lewat admin (hapus satu per satu atau massal) atau database; belum ada alat khusus.
- **Backup** menyimpan salinan yang sudah dihapus `leads:prune`. Tetapkan masa simpan backup (mis. 30–90 hari) dan hapus yang lebih tua (SECURITY_CHECKLIST_DEPLOY.md bagian 3).
- Log keamanan berisi alamat IP (untuk penelusuran) dan email yang dimasking; disimpan 90 hari (`LOG_SECURITY_DAYS`).

## Status keamanan

| Kategori | Status | Commit |
|---|---|---|
| B. XSS (rich text, email lead, URL sosial) | selesai | 421ee25 |
| F. Upload (re-encode GD, tanpa SVG, folder tanpa eksekusi) | selesai | 020f7b2 |
| G. Header, HTTPS, proxy, server | selesai; CSP berjalan **report-only** sampai Anda menyalakan `enforce` | edefb6b |
| D. Akses (`is_admin`, policy leads) | selesai | 05303a9 |
| C. Autentikasi (lockout, profil, `admin:create`, Turnstile opsional, ADMIN_PATH) | selesai; 2FA = item lanjutan | 8f5bfb1 |
| J. Halaman error dan log keamanan | selesai | 78b241d |
| K + H. Form Request, retensi lead | selesai | dee7d4a |
| E + I. Session dan dependency | selesai | 221b2db |

## Yang tersisa sebelum go-live

Domain, SSL (AutoSSL), SMTP + SPF/DKIM, admin pertama, cron, daftar penerima leads, dan uji akhir: lihat `SECURITY_CHECKLIST_DEPLOY.md` bagian 1 sampai 3 dan 7. Setelah itu nyalakan `FORCE_HTTPS`, periksa konsol untuk CSP, lalu `CSP_MODE=enforce`.
