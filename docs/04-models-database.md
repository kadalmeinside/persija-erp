# Database dan ERD

Migration pada repository adalah baseline yang dapat dibangun ulang dengan `php artisan migrate:fresh` pada lokal/testing. Jangan menjalankan perintah tersebut di production.

## Relasi inti

```text
users 1---1 tbl_karyawan
tbl_karyawan 1---N tbl_absensi
tbl_karyawan 1---N tbl_pengajuan_cuti
tbl_jenis_cuti 1---N tbl_saldo_cuti
tbl_pengajuan_cuti 1---N tbl_approval_process
tbl_payroll 1---N tbl_payroll_detail
tbl_jurnal_header 1---N tbl_jurnal_detail
tbl_budget_master 1---N tbl_budget_detail
users 1---N attendance_challenges
```

## Constraint penting

- `tbl_karyawan.user_id` unique.
- `tbl_absensi (id_karyawan, tanggal)` unique.
- Payroll periode dan employee detail unique.
- Budget detail `(id_budget_master, bulan, tahun)` unique; bulan 1–12; nominal tidak negatif.
- Jurnal detail menolak nilai negatif dan debit+kredit sekaligus.
- Jurnal header memiliki `source_type`, `source_event`, dan `posting_batch_id`.
- Attendance challenge memiliki nonce hash unique, expiry, consumed state, dan request ID per user unique.
- `tbl_penyusutan.id_jurnal` menjadi FK ke jurnal depresiasi; `jurnal_ref` dipertahankan untuk display/legacy.
- `tbl_laporan_detail` menyimpan `id_tax_type`, rate snapshot, dan nominal pajak per item settlement.
- `tbl_pengajuan_cuti` menyimpan status `Cancelled`, actor, waktu, dan alasan pembatalan.
- Kalender memiliki index tanggal pada event dan cuti; API membatasi query satu bulan dan maksimal 500 item per tipe.

Saldo cuti unlimited ditentukan oleh `tbl_jenis_cuti.is_unlimited`; API saldo mengirim `is_unlimited` agar client tidak menampilkan angka kuota.

## Implikasi finansial

Check constraint tidak menggantikan validasi service. Total debit-kredit, periode tertutup, idempotensi, dan reversal wajib divalidasi dalam transaction aplikasi.
