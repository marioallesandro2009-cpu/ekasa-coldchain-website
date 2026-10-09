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
   `ID | Diterima (WIB) | Nama | Email | Telepon | Perusahaan | Negara | Layanan | Pesan | Bahasa | Kategori | Jumlah (ton) | Kontainer | Negara tujuan`
   (14 kolom, A sampai N. Empat kolom terakhir adalah pertanyaan pengiriman di form Kontak; Sheet lama cukup menambahkan K sampai N di baris 1.)
3. Menu **Ekstensi → Apps Script**. Hapus isi bawaan, tempel kode ini, ganti `SECRET` dengan kata sandi acak panjang
   (mis. 32 karakter; simpan di password manager):

```javascript
const SECRET = 'GANTI-DENGAN-KATA-SANDI-ACAK-PANJANG';
// Email pemberitahuan: setiap pesan baru dikirim ke Gmail ini dalam bentuk tabel + tautan ke Sheet.
// Kosongkan ('') bila tidak mau email dari Apps Script.
const NOTIFY_TO = 'GANTI@gmail.com';

function doPost(e) {
  try {
    const d = JSON.parse(e.postData.contents);
    if (d.secret !== SECRET) return out({ ok: false });

    // Dari panel admin: orang yang dihapus dari daftar penerima juga dicabut aksesnya ke Sheet ini.
    if (d.action === 'revoke') return out(revoke(d.email));

    // Satu permintaan pada satu waktu, supaya penambahan dan pengurutan baris tidak saling menimpa.
    const lock = LockService.getScriptLock();
    lock.waitLock(20000);
    try {
      const sheet = SpreadsheetApp.getActiveSpreadsheet().getSheets()[0];
      // Cegah baris ganda bila pengiriman diulang: kolom A berisi ID pesan.
      const ids = sheet.getRange(2, 1, Math.max(sheet.getLastRow() - 1, 1), 1).getValues().flat();
      if (!ids.includes(d.id)) {
        // Awali sel teks dengan ' agar rumus (=, +, -, @) dari pengunjung tidak dijalankan Sheets.
        const t = (v) => (v == null ? '' : "'" + String(v));
        sheet.appendRow([d.id, d.received_at, t(d.name), t(d.email), t(d.phone), t(d.company), t(d.country), t(d.service), t(d.message), d.language, t(d.category), d.quantity_tons == null ? '' : d.quantity_tons, t(d.container), t(d.destination_country)]);
        // Urutan baris selalu dari ID terkecil (terlama) ke terbesar (terbaru), walau pesan masuk terlambat atau dikirim ulang.
        sortById(sheet);
        if (NOTIFY_TO) notify(d);
      }
    } finally {
      lock.releaseLock();
    }
    return out({ ok: true });
  } catch (err) {
    return out({ ok: false });
  }
}

// Mengurutkan semua baris data (di bawah judul) menurut kolom A (ID), dari yang terlama ke terbaru.
function sortById(sheet) {
  const last = sheet.getLastRow();
  if (last > 2) sheet.getRange(2, 1, last - 1, sheet.getLastColumn()).sort({ column: 1, ascending: true });
}

// Gmail menerima satu email per pesan baru: tabel isi pesan + tombol ke Sheet-nya.
function notify(d) {
  const esc = (v) => String(v == null || v === '' ? '-' : v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
  const rows = [['Nama', d.name], ['Email', d.email], ['Telepon', d.phone], ['Perusahaan', d.company], ['Negara', d.country], ['Layanan', d.service], ['Pesan', d.message], ['Kategori', d.category], ['Jumlah (ton)', d.quantity_tons], ['Kontainer', d.container], ['Negara tujuan', d.destination_country], ['Diterima (WIB)', d.received_at], ['Bahasa', d.language]];
  const table = rows.map(([k, v]) => '<tr><td style="padding:6px 12px;border:1px solid #ddd;background:#f6f8f9"><b>' + k + '</b></td><td style="padding:6px 12px;border:1px solid #ddd">' + esc(v) + '</td></tr>').join('');
  const url = SpreadsheetApp.getActiveSpreadsheet().getUrl();
  MailApp.sendEmail({
    to: NOTIFY_TO,
    replyTo: d.email,
    subject: 'Pesan baru dari website: ' + d.name,
    htmlBody: '<p>Ada pesan baru dari form Kontak ekasacoldchain.com.</p><table style="border-collapse:collapse">' + table + '</table><p><a href="' + url + '">Buka Google Sheet</a></p>',
  });
}

// Menghapus satu alamat dari daftar Bagikan (editor, pemberi komentar, pembaca). Pemilik Sheet tidak pernah dicabut.
function revoke(email) {
  const target = String(email || '').trim().toLowerCase();
  const file = DriveApp.getFileById(SpreadsheetApp.getActiveSpreadsheet().getId());
  let owner = '';
  try { owner = file.getOwner().getEmail().toLowerCase(); } catch (err) {}
  if (!target || target === owner) return { ok: true, revoked: false };
  [() => file.removeEditor(target), () => file.removeCommenter(target), () => file.removeViewer(target)].forEach((fn) => { try { fn(); } catch (err) {} });
  return { ok: true, revoked: true };
}

function out(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}
```

3b. Ganti `NOTIFY_TO` dengan alamat Gmail yang menerima pemberitahuan. Saat pertama kali diterapkan, Google meminta izin
   mengirim email atas nama Anda (`MailApp`); izinkan. Batas kiriman Apps Script: sekitar 100 email per hari untuk Gmail biasa,
   sekitar 1.500 untuk Google Workspace.
4. **Terapkan → Deployment baru → jenis: Aplikasi web.** Jalankan sebagai: **Saya**. Yang memiliki akses: **Siapa saja**
   (alamat web app-nya yang panjang dan acak, ditambah `SECRET`, yang menjaga). Klik **Terapkan**, izinkan akses, lalu
   salin **URL aplikasi web** (berakhiran `/exec`).
5. Setiap kali kode Apps Script diubah, buat deployment versi baru (URL tetap sama bila memilih "Kelola deployment → edit").

### Urutan baris di Sheet

Kode di atas mengurutkan Sheet menurut kolom **A (ID)** dari kecil ke besar setiap kali ada baris baru, jadi pesan **terlama ada di atas dan terbaru di bawah**,
bahkan kalau sebuah pesan terlambat masuk (misalnya karena sempat gagal lalu dikirim ulang). Untuk merapikan baris yang sudah terlanjur tidak berurutan:
blok baris 2 sampai terakhir, lalu menu **Data, Urutkan rentang, Opsi urutan lanjutan, kolom A, A ke Z**.

### Mencabut akses seseorang dari panel admin

Kode di atas sudah memuat fungsi `revoke`. Saat sebuah alamat **dihapus dari daftar penerima** di Pengaturan Situs (tab Kontak), atau saat
akun admin seseorang **dicabut** di menu Akses, situs memanggil fungsi itu dan orang tersebut otomatis dihapus dari daftar **Bagikan** Sheet.

Syarat supaya berhasil:

1. **Perbarui kode Apps Script** ke versi terbaru di atas, lalu **Terapkan, Kelola deployment, ikon pensil, Versi baru, Terapkan**.
   Google akan meminta izin tambahan (akses ke Google Drive, untuk mengubah daftar Bagikan): klik **Beri akses**, **Lanjutan**, **Buka ... (tidak aman)**, **Izinkan**.
2. Di Google Sheet, klik **Bagikan** dan pastikan **Akses umum = Dibatasi** (bukan "Siapa saja yang memiliki link"). Kalau Sheet bisa dibuka
   lewat link oleh siapa saja, menghapus nama seseorang tidak menutup aksesnya.
3. Orang yang dicabut hanya kehilangan akses ke Sheet. **Email dan Excel yang sudah ia terima di kotak suratnya sendiri tidak bisa ditarik kembali.**
4. Pemilik Sheet (akun `leads@`) tidak pernah dicabut. Kalau sebuah alamat tidak bisa dicabut otomatis, panel admin memberi peringatan dan Anda
   mencabutnya manual lewat tombol **Bagikan**.

Jika kode Apps Script belum diperbarui, panel admin memberi peringatan bahwa akses Sheet "belum bisa dicabut otomatis", dan daftar penerima tetap tersimpan.

## B. Hubungkan ke situs (di server)

Isi di `.env` server (nano), lalu `php artisan config:cache`:

```
GOOGLE_SHEETS_WEBHOOK_URL=https://script.google.com/macros/s/XXXXXXXX/exec
GOOGLE_SHEETS_SECRET=kata-sandi-yang-sama-dengan-SECRET-di-Apps-Script
# Opsional: alamat Sheet-nya, untuk tombol "Buka Google Sheet" di email pemberitahuan
GOOGLE_SHEETS_VIEW_URL=https://docs.google.com/spreadsheets/d/ID-SHEET-ANDA/edit
```

Uji: kirim satu pesan lewat form Kontak; satu baris baru muncul di Sheet dan satu email berisi tabel masuk ke Gmail `NOTIFY_TO` dalam beberapa detik. Uji manual dari terminal:
`php artisan leads:sync-sheet` (mengirim pesan yang belum ada di Sheet; **menyalakan fitur ini pertama kali akan mengirim
semua pesan lama yang sudah ada di database**, dan tiap pesan lama juga memicu satu email ke `NOTIFY_TO`. Bila sudah ada banyak pesan,
kosongkan `NOTIFY_TO` dulu (`''`) sampai sinkronisasi pertama selesai, lalu isi lagi dan buat deployment baru).

## C. Catatan privasi

- Sheet berisi data pribadi (nama, email, telepon). Bagikan hanya ke orang yang perlu dan aktifkan 2FA di akun Google-nya.
- `leads:prune` (retensi 60 hari IP/user-agent, 12 bulan hapus pesan) hanya membersihkan database situs. **Baris di Sheet
  tidak ikut terhapus**; hapus manual sesuai kebijakan data perusahaan. Alamat IP dan user-agent tidak dikirim ke Sheet.
- Bila `SECRET` bocor, ganti di Apps Script (deployment baru) dan di `.env`.
