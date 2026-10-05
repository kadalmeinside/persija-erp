# Approval Workflow

Approval disimpan pada `tbl_approval_process` dan dibuat oleh `ApprovalService`.

## Data proses

- Subject: pengajuan dana, cuti, pinjaman, atau invoice.
- `id_karyawan_target`: pihak yang harus bertindak.
- `id_karyawan_action`: pihak yang benar-benar bertindak.
- `level_order`, `status`, `tgl_aksi`, catatan, dan label aksi.

## Status

Status yang digunakan harus mengikuti enum/schema aktif. Status umum adalah `Pending`, `Approved`, `Rejected`, `Revision`, dan `Skipped`.

## Aturan implementasi

- Actor harus berasal dari user yang terautentikasi.
- Approval mobile cuti membutuhkan PIN.
- Perubahan status dan saldo harus berada dalam transaction.
- Satu subject tidak boleh diselesaikan oleh actor yang tidak ditargetkan.

Catatan: schema saat ini masih memakai beberapa nullable subject FK. Constraint eksklusivitas subject dan audit history lintas modul adalah pekerjaan lanjutan.
