# Kesiapan produksi

> Dokumen ini akan diisi lengkap pada tahap hardening keamanan (saat ini ditahan).
> Sementara, berisi item lanjutan yang sudah diketahui.

## Item lanjutan

### Data pribadi pada Pesan Masuk (form Kontak): kebijakan retensi

Tabel `contact_messages` menyimpan data pribadi pengunjung:

| Kolom | Isi | Ditampilkan di |
|---|---|---|
| `name`, `email`, `phone`, `company`, `message` | diisi pengunjung | admin (tabel & detail), email notifikasi |
| `ip`, `user_agent` | dicatat server saat submit (untuk rate limit dan penelusuran spam) | **hanya** halaman detail di admin; tidak pernah tampil di situs publik |

Belum ada kebijakan retensi atau penghapusan otomatis: pesan tersimpan sampai dihapus manual
(aksi Hapus / hapus massal di admin). **Tidak diimplementasikan sekarang, dicatat sebagai
pekerjaan lanjutan** bila nanti dibutuhkan kepatuhan privasi (UU PDP Indonesia, atau GDPR untuk
pengunjung dari luar negeri). Yang perlu diputuskan saat itu:

- lama penyimpanan (mis. hapus otomatis pesan lebih dari 12–24 bulan; `ip`/`user_agent` bisa
  dikosongkan lebih cepat, mis. 30–90 hari, karena hanya berguna untuk penelusuran spam);
- cara menghapus permintaan subjek data (hapus semua pesan dari satu alamat email);
- pemberitahuan privasi di dekat form ("data Anda dipakai untuk membalas permintaan ini") dan halaman kebijakan privasi;
- backup database juga memuat data ini (lihat DEPLOY.md bagian D): retensi harus mencakup backup.

Implementasi yang mungkin: perintah artisan terjadwal yang menghapus/menganonimkan baris lama
(memerlukan cron `schedule:run`, lihat DEPLOY.md A13), atau aksi massal di admin.
