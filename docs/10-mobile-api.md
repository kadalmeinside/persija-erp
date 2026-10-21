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

`POST /cuti/cancel/{id}` menerima `reason`. User hanya dapat membatalkan pengajuannya sendiri sebelum tanggal mulai. Pembatalan mengembalikan saldo non-unlimited secara atomic dan hanya dapat dilakukan sekali.

Approver menggunakan `GET /cuti/approvals` lalu `POST /cuti/approve/{id}` dengan payload:

```json
{"status":"Approved","pin":"123456","catatan":"Disetujui"}
```

`pin` wajib enam digit dan diverifikasi terhadap PIN user approver. Status `Rejected` memakai endpoint dan PIN yang sama.

## Dashboard Direktur

`GET /dashboard/home` mengembalikan dashboard personal untuk semua user. User dengan role `Direktur` juga menerima `director_overview`, yang berisi tugas jatuh tempo hari ini, tugas overdue yang belum selesai, cuti approved yang sedang berlangsung hari ini, dan karyawan aktif yang belum memiliki absensi hari ini. Payload ini hanya ditambahkan oleh backend untuk role Direktur; client tidak boleh menjadikannya sebagai kontrol otorisasi.

## Approval Center

`GET /api/v1/approvals` mengembalikan approval process yang target employee-nya
adalah employee pada token login. Default hanya `Pending`; gunakan
`?status=all` untuk riwayat milik employee tersebut.

`GET /api/v1/approvals/{approval}` mengembalikan detail dan timeline seluruh
step approval untuk dokumen tersebut. Timeline menggunakan employee target dan
actor aktual; label aksi hanya berasal dari konfigurasi rule dan bukan asumsi
jabatan.

`POST /api/v1/approvals/{approval}/action` menerima:

```json
{"status":"Approved","pin":"123456","catatan":"Diproses"}
```

Hanya `id_karyawan_target` pada step `Pending` yang dapat memprosesnya. Role
tidak memberikan hak approval secara otomatis. Endpoint ini mendukung
`Approved` dan `Rejected`, termasuk reversal saldo cuti non-unlimited saat cuti
ditolak.

Secara database, setiap process menunjuk tepat satu baris
`tbl_approval_documents` melalui `id_approval_document`. Registry tersebut
menyimpan `document_type` dan `document_id`, sehingga process tidak lagi
memiliki empat FK dokumen nullable yang dapat terisi bersamaan. `document_type`
yang tersedia adalah `Pengajuan`, `Cuti`, `Pinjaman`, dan `Invoice`.

Login mobile memakai kebijakan single-device: login baru mencabut seluruh token Sanctum lama untuk user yang sama.

## Face matching & Registrasi Wajah

Mobile melakukan ekstraksi dan matching descriptor secara on-device menggunakan model TFLite (MobileFaceNet). Descriptor database disimpan dalam profil karyawan dalam format multi-descriptor JSON (kompatibel lintas web 128D & mobile 192D).

### Pendaftaran Wajah Pertama Kali (Enrollment)

`POST /api/v1/auth/register-face`

Menerima JSON:
```json
{
  "face_descriptor": [0.0123, -0.0456, "... (192 float)"]
}
```

Endpoint ini memvalidasi array embedding float berukuran 192, menyimpan embedding ke `tbl_karyawan.face_embedding`, dan mengembalikan status sukses beserta data user terbaru. Jika karyawan belum memiliki embedding terdaftar, mobile akan menampilkan prompt dialog panduan animasi (`FaceRegistrationPromptDialog`) dan mengarahkan ke `FaceRegistrationScreen`.

## Akses Role Freelance

Pengguna dengan role `Freelance` diperlakukan dengan kebijakan *least privilege*:
- Di web: Mengakses root dashboard otomatis diarahkan (*redirect*) ke antarmuka absensi. Akses ke modul finance, payroll, approval, atau master data ditolak dengan response 403.
- Di API/Mobile: Hanya dapat mengakses endpoint absensi (`/absensi/*`) dan profil. Mobile menampilkan kartu riwayat kehadiran khusus freelance dan menyembunyikan modul cuti/approval internal.

## Sistem Notifikasi

- `GET /api/v1/notifications`: Mengambil daftar notifikasi terikat `target_employee` beserta total `unread_count`.
- `POST /api/v1/notifications/{id}/read`: Menandai notifikasi telah dibaca.
- `GET /api/v1/notifications/preferences`: Mengambil konfigurasi kanal preferensi notifikasi karyawan.
- `POST /api/v1/notifications/preferences`: Memperbarui preferensi kanal (in-app, email, push).
- `POST /api/v1/notifications/device-token`: Mendaftarkan FCM device token perangkat untuk push notification real-time.

## Employee Self-Service (ESS)

- `POST /api/v1/ess/koreksi-absensi`: Mengajukan koreksi jam kehadiran dengan alasan dan bukti.
- `POST /api/v1/ess/dinas-luar`: Mengajukan penugasan dinas luar kota/lapangan.
- `GET /api/v1/ess/payslips`: Menampilkan histori dan rincian slip gaji pribadi (read-only).
- `POST /api/v1/ess/reimbursement`: Mengajukan klaim reimbursement operasional dengan bukti transaksi.
