# Karyawan keluar: mencabut akses ke request dan data

Daftar periksa untuk saat seseorang berhenti bekerja di PT Ekasa Cold Chain. Lakukan **semuanya pada hari yang sama**.

## Yang bisa dan tidak bisa dicabut

| | Bisa dicabut? |
|---|---|
| Akses ke panel admin situs (Pesan Masuk, unduhan, pengaturan) | **Ya, seketika.** |
| Menerima request baru lewat email | **Ya**, hapus dari daftar penerima (otomatis saat akses admin dicabut, bila emailnya sama). |
| Akses ke Google Sheet leads | **Ya**, cabut dari tombol Bagikan. |
| Akses ke kotak surat perusahaan (`leads@`, `nama@ekasacoldchain.com`) | **Ya**, ganti password atau nonaktifkan di cPanel. |
| Email dan file Excel yang **sudah masuk ke kotak surat pribadi** (misalnya Gmail pribadi), atau sudah diunduh, disalin, difoto | **Tidak.** Perusahaan tidak bisa menariknya dari jarak jauh. |

Karena itu aturan utamanya: **request hanya dikirim ke kotak surat milik perusahaan**, bukan ke email pribadi karyawan.
Data yang sudah dipegang pribadi ditangani lewat perjanjian kerja atau NDA, bukan lewat sistem.

## Langkah-langkah

1. **Panel admin situs**
   - Buka `ekasacoldchain.com/admin`, menu **Akses**, **Akun Admin**, klik **Cabut akses** pada orang tersebut.
   - Efeknya langsung: ia keluar dari panel, semua sesi login-nya berakhir, dan emailnya dihapus dari daftar penerima pesan (jika ada).
   - Dari server (opsional, untuk teknisi): `php artisan admin:revoke email@orang.com` (perintah ini meminta konfirmasi).
   - Akun tidak dihapus, sehingga ada catatan siapa yang pernah punya akses. Akses bisa dipulihkan lewat **Pulihkan akses**.
   - Untuk akses sementara (kontraktor, magang), pakai **Batas akses**: akses berakhir sendiri pada tanggal itu.
2. **Daftar penerima pesan.** Buka **Pengaturan Situs**, tab **Kontak**, hapus baris alamat orang itu, lalu **Simpan**. Ini cukup untuk orang yang hanya menerima email (bukan akun admin). Ia berhenti menerima pesan baru dan aksesnya ke Google Sheet dicabut otomatis.
3. **Google Sheet.** Cukup dengan langkah 1 atau 2: menghapus alamatnya dari daftar penerima (atau mencabut akun adminnya) otomatis menghapusnya dari **Bagikan** Sheet, asal kode Apps Script sudah versi terbaru (lihat `google-sheet-leads.md`). Kalau panel menampilkan peringatan "belum bisa dicabut otomatis", buka Sheet **Leads Website Ekasa**, klik **Bagikan**, dan hapus namanya. Pastikan **Akses umum = Dibatasi**. Matikan juga opsi "unduh, cetak, dan salin" untuk pembaca dan pemberi komentar supaya data tidak mudah diekspor.
4. **Kotak surat.** Di cPanel, **Email Accounts**: nonaktifkan atau hapus kotak surat atas namanya. Untuk kotak bersama (`leads@`), ganti passwordnya dan bagikan hanya ke yang berhak. Hapus juga **Forwarder** dari kotak bersama ke alamat orang itu.
5. **Kunci rahasia.** Jika orang itu pernah memegang Apps Script atau `.env` server, ganti `SECRET` Google Sheet (Apps Script dan `GOOGLE_SHEETS_SECRET`) dan password kotak surat pengirim (`MAIL_PASSWORD`).
6. **Akun lain.** Cabut dari Google Search Console, cPanel, dan GitHub bila ada.

## Mencegah masalah ini sejak awal

- Kirim request hanya ke `leads@` (kotak surat perusahaan), lalu bagikan aksesnya ke orang yang berhak.
- Beri akun admin masing-masing orang, jangan berbagi satu login.
- Untuk akses sementara, isi **Batas akses** sejak akun dibuat.
