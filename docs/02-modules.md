# Modul dan Alur Bisnis

## HR dan ESS

- Karyawan dan akun user.
- Manajemen Kontrak Kerja Karyawan: Penerbitan kontrak (PKWT, PKWTT, Profesional, Freelance, Magang/Internship), versioning dokumen (`v1`, `v2`, dst.), dan sinkronisasi otomatis ke riwayat karir (`tbl_karyawan_riwayat_karir`).
- Master Template Kontrak: Pengelolaan template hukum dinamis dengan TipTap WYSIWYG editor, variabel `{TAG}` dinamis, filter searchable tags, dan modal preview live sample A4.
- Jenis cuti, saldo cuti, pengajuan cuti, dan approval.
- Absensi web/mobile dengan lokasi, foto, face matching on-device, dan pendaftaran wajah (`register-face`).
- Modul ESS: Koreksi absensi, penugasan dinas luar, slip gaji pribadi (payslip read-only), dan reimbursement operasional.
- Payroll dan detail payroll.
- Pinjaman karyawan dan angsuran.
- Task dan company event.
- Kalender & Room Booking: Penjadwalan agenda, pencegahan bentrok ruang rapat (Ruang Utama, Ruang 2, Basecamp), public calendar token sharing anti-AI scraper, dan ekspor feed kalender format ICS.
- Notifikasi: Registrasi FCM token, preferensi kanal notifikasi, dan badge unread counter.

## Finance

- Chart of Accounts, jurnal, kas/bank, periode akuntansi.
- Pengajuan dana, invoice, pembayaran, pajak.
- Budget, program kerja, pos anggaran, dan pacing bulanan.
- Aset dan depresiasi.
- Rekonsiliasi kas/bank otomatis dan deteksi diskrepansi transaksi finansial.

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
2. Jika belum memiliki descriptor wajah, mobile menampilkan dialog panduan animasi dan mengarahkan ke pendaftaran wajah front camera.
3. Mobile meminta challenge `clock-in` atau `clock-out` (challenge auto-renew saat aplikasi kembali dari background atau sebelum submit).
4. Backend mengembalikan nonce single-use.
5. Mobile menormalisasi orientasi foto (`bakeOrientation`), lalu mengirim foto, GPS, request ID, dan nonce.
6. Backend memvalidasi challenge lalu menjalankan `AbsensiService`.
