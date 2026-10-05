# Modul dan Alur Bisnis

## HR dan ESS

- Karyawan dan akun user.
- Jenis cuti, saldo cuti, pengajuan cuti, dan approval.
- Absensi web/mobile dengan lokasi, foto, dan riwayat.
- Payroll dan detail payroll.
- Pinjaman karyawan dan angsuran.
- Task dan company event.

## Finance

- Chart of Accounts, jurnal, kas/bank, periode akuntansi.
- Pengajuan dana, invoice, pembayaran, pajak.
- Budget, program kerja, pos anggaran, dan pacing bulanan.
- Aset dan depresiasi.

## Alur cuti

1. User memilih jenis cuti dan tanggal.
2. Backend menghitung hari kerja, hari libur, overlap, lampiran, dan saldo.
3. Saldo non-unlimited di-reserve secara atomic.
4. Approval dibuat.
5. Approver approve/reject dengan autentikasi PIN.
6. Reject mengembalikan saldo yang sebelumnya di-reserve.
7. Jenis cuti unlimited tidak mengurangi saldo.
8. User dapat membatalkan pengajuan Pending/Approved sebelum tanggal mulai; pembatalan idempotent mengembalikan saldo non-unlimited.

## Alur attendance mobile

1. Mobile melakukan pre-check device, lokasi, dan face matching.
2. Mobile meminta challenge `clock-in` atau `clock-out`.
3. Backend mengembalikan nonce single-use.
4. Mobile mengirim foto, GPS, request ID, dan nonce.
5. Backend memvalidasi challenge lalu menjalankan `AbsensiService`.
