# Service Layer

Service utama:

- `AbsensiService`: geofence, status attendance, dan penyimpanan foto.
- `ApprovalService`: inisialisasi, approve, reject, level, dan audit approval.
- `GLService`: validasi balance, posting jurnal, source traceability, dan batch ID.
- `PayrollService`: kalkulasi payroll dan posting terkait.
- `PinjamanService`: pinjaman dan jadwal angsuran.
- `PeriodClosingService`: menolak posting ke periode tertutup.

Service harus dipanggil dalam transaction untuk operasi yang mengubah lebih dari satu aggregate. Jangan menyalin business rule ke controller atau mobile.
