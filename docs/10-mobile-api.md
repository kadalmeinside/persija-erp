# Mobile API

Base URL dan token mengikuti environment aplikasi. Semua endpoint membutuhkan Sanctum token.

## Attendance challenge

`POST /api/v1/absensi/challenge`

```json
{"action":"clock-in","security_metadata":{"platform":"android"}}
```

Response berisi `action`, `request_id`, `nonce`, dan `expires_at`. Challenge terikat user/action, berlaku singkat, dan hanya dapat digunakan sekali.

## Clock-in/out

Multipart wajib:

- `latitude`
- `longitude`
- `photo`
- `request_id`
- `nonce`

Clock-in juga menerima `is_dinas_luar` dan `catatan`.

## Cuti

`GET /cuti/balances` mengembalikan `saldo_awal`, `saldo_terpakai`, `saldo_akhir`, dan `is_unlimited`. Bila `is_unlimited=true`, client harus menampilkan `Unlimited`, bukan angka.

`POST /cuti/request` menerima jenis cuti, tanggal mulai/selesai, keterangan, dan lampiran opsional/required sesuai jenis cuti.

## Face matching

Mobile melakukan ekstraksi dan matching descriptor secara on-device menggunakan model TFLite. Descriptor database berasal dari profile user. Backend belum melakukan verifikasi descriptor server-side.
