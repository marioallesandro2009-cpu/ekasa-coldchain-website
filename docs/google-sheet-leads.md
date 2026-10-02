# Leads form Kontak ke Google Sheet

Setiap pesan baru dari form Kontak, selain disimpan di database (menu **Pesan Masuk**) dan dikirim lewat email, juga
ditambahkan sebagai satu baris di Google Sheet milik PT Ekasa Cold Chain. Mekanismenya: situs mengirim data ke sebuah
**Google Apps Script web app** yang menulis baris ke Sheet. Tidak perlu akun layanan Google atau kunci API.

Bila pengiriman ke Sheet gagal (internet putus, Apps Script error), pengunjung tetap melihat "terkirim" dan pesan tetap
tersimpan. Pesan itu ditandai belum terkirim ke Sheet dan dicoba lagi otomatis tiap 15 menit (`leads:sync-sheet`, lewat
cron `schedule:run` yang sudah terpasang).

## A. Siapkan Sheet dan Apps Script (dilakukan akun Google PT Ekasa)

1. Buka <https://sheets.new> dengan akun Google perusahaan, beri nama mis. **Leads Website Ekasa**.
2. Baris 1 (judul kolom), persis urutan ini:
   `ID | Diterima (WIB) | Nama | Email | Telepon | Perusahaan | Negara | Layanan | Pesan | Bahasa`
3. Menu **Ekstensi → Apps Script**. Hapus isi bawaan, tempel kode ini, ganti `SECRET` dengan kata sandi acak panjang
   (mis. 32 karakter; simpan di password manager):

```javascript
const SECRET = 'GANTI-DENGAN-KATA-SANDI-ACAK-PANJANG';

function doPost(e) {
  try {
    const d = JSON.parse(e.postData.contents);
    if (d.secret !== SECRET) return out({ ok: false });

    const sheet = SpreadsheetApp.getActiveSpreadsheet().getSheets()[0];
    // Cegah baris ganda bila pengiriman diulang: kolom A berisi ID pesan.
    const ids = sheet.getRange(2, 1, Math.max(sheet.getLastRow() - 1, 1), 1).getValues().flat();
    if (!ids.includes(d.id)) {
      // Awali sel teks dengan ' agar rumus (=, +, -, @) dari pengunjung tidak dijalankan Sheets.
      const t = (v) => (v == null ? '' : "'" + String(v));
      sheet.appendRow([d.id, d.received_at, t(d.name), t(d.email), t(d.phone), t(d.company), t(d.country), t(d.service), t(d.message), d.language]);
    }
    return out({ ok: true });
  } catch (err) {
    return out({ ok: false });
  }
}

function out(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}
```

4. **Terapkan → Deployment baru → jenis: Aplikasi web.** Jalankan sebagai: **Saya**. Yang memiliki akses: **Siapa saja**
   (alamat web app-nya yang panjang dan acak, ditambah `SECRET`, yang menjaga). Klik **Terapkan**, izinkan akses, lalu
   salin **URL aplikasi web** (berakhiran `/exec`).
5. Setiap kali kode Apps Script diubah, buat deployment versi baru (URL tetap sama bila memilih "Kelola deployment → edit").

## B. Hubungkan ke situs (di server)

Isi di `.env` server (nano), lalu `php artisan config:cache`:

```
GOOGLE_SHEETS_WEBHOOK_URL=https://script.google.com/macros/s/XXXXXXXX/exec
GOOGLE_SHEETS_SECRET=kata-sandi-yang-sama-dengan-SECRET-di-Apps-Script
```

Uji: kirim satu pesan lewat form Kontak; satu baris baru muncul di Sheet dalam beberapa detik. Uji manual dari terminal:
`php artisan leads:sync-sheet` (mengirim pesan yang belum ada di Sheet; **menyalakan fitur ini pertama kali akan mengirim
semua pesan lama yang sudah ada di database**).

## C. Catatan privasi

- Sheet berisi data pribadi (nama, email, telepon). Bagikan hanya ke orang yang perlu dan aktifkan 2FA di akun Google-nya.
- `leads:prune` (retensi 60 hari IP/user-agent, 12 bulan hapus pesan) hanya membersihkan database situs. **Baris di Sheet
  tidak ikut terhapus**; hapus manual sesuai kebijakan data perusahaan. Alamat IP dan user-agent tidak dikirim ke Sheet.
- Bila `SECRET` bocor, ganti di Apps Script (deployment baru) dan di `.env`.
