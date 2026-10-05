# Route dan Otorisasi

## Web

Route web berada di `routes/web.php` dan `routes/admin/*`. Akses dibatasi middleware auth, role/permission, dan middleware bisnis seperti periode akuntansi.

## API mobile

Prefix utama API adalah `/api/v1`. Endpoint penting:

| Method | Endpoint | Fungsi |
|---|---|---|
| POST | `/absensi/challenge` | Membuat challenge attendance |
| POST | `/absensi/clock-in` | Clock-in dengan foto, GPS, nonce |
| POST | `/absensi/clock-out` | Clock-out dengan foto, GPS, nonce |
| GET | `/absensi/today` | Status absensi hari ini |
| GET | `/absensi/history` | Riwayat absensi |
| GET | `/cuti/jenis` | Jenis cuti |
| GET | `/cuti/balances` | Saldo cuti |
| POST | `/cuti/request` | Mengajukan cuti |
| POST | `/cuti/cancel/{id}` | Membatalkan cuti milik user sebelum dimulai |
| GET | `/cuti/requests` | Riwayat pengajuan user |
| GET | `/cuti/approvals` | Approval yang menjadi target user |

Sanctum authentication dan route middleware adalah sumber kebenaran akses; UI tidak boleh dianggap sebagai authorization.
