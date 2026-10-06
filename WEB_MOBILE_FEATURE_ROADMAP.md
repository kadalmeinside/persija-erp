# Web & Mobile Feature Roadmap

Dokumen ini menjadi daftar pantau fitur ERP untuk backend, web, dan mobile.
Status harus diperbarui setiap ada perubahan implementasi, pengujian, atau
keputusan arsitektur.

Repository terkait:

- Backend/web: `/Users/macbook/Application/persija-erp`
- Mobile: `/Users/macbook/Application/psjerp-mobile`

## Pembagian tanggung jawab client

### Web

Web menjadi client utama untuk:

- konfigurasi master data dan approval rule;
- administrasi karyawan, departemen, dan permission;
- transaksi kompleks dan bulk operation;
- finance, budget, accounting, payroll, procurement, dan asset;
- audit, reporting, export, dan reconciliation;
- monitoring organisasi secara detail;
- administrasi delegasi/reassignment approval.

### Mobile

Mobile menjadi client utama untuk:

- employee self-service;
- absensi dan aktivitas lapangan;
- pengajuan sederhana;
- approval cepat;
- notifikasi dan tindakan yang membutuhkan respons segera;
- monitoring ringkas sesuai role/permission.

Web dan mobile harus memakai aturan otorisasi backend yang sama. UI salah satu
client tidak boleh menjadi sumber keamanan.

## Prinsip otorisasi

Role/permission dan assignment approval memiliki fungsi yang berbeda:

- **Role/permission** menentukan akses modul dan dashboard umum.
- **Employee assignment** menentukan siapa yang berhak memproses approval tertentu.
- Approval tidak boleh diberikan hanya karena seseorang memiliki role Direktur,
  Manager, HR, atau role lain.
- Backend selalu menjadi sumber otorisasi. Visibility menu di mobile bukan
  security control.

Kolom `tbl_approval_process.id_karyawan_target` adalah employee yang ditunjuk
untuk memproses approval aktif. Validasi aksi juga harus membandingkan employee
yang login dengan kolom tersebut.

## Model approval yang benar

Alur dasar:

```text
Employee mengajukan
        |
        v
Approval rule departemen dan tipe dokumen memilih employee approver
        |
        v
Employee approver yang ditunjuk memproses approval
        |
        v
Selesai
```

Setiap departemen dapat memiliki konfigurasi berbeda. Employee approver tidak
ditentukan dari jabatan atau asumsi hierarki global. `level_order`, bila dipakai,
hanya mengatur urutan step pada rule dokumen/departemen tersebut; bukan berarti
level tersebut identik dengan jabatan tertentu.

Sebuah dokumen dapat memiliki beberapa employee approver yang diproses
berurutan sesuai rule departemennya:

```text
Employee A mengajukan
        |
        v
Employee B yang ditunjuk pada step aktif
        |
        v
Employee C yang ditunjuk pada step berikutnya (jika dikonfigurasi)
        |
        v
Selesai
```

Jika hanya ada satu employee approver pada rule, alurnya cukup:

```text
Employee mengajukan -> Employee approver -> Selesai
```

Status proses yang perlu dipahami mobile:

- `Pending`: step aktif dan menunggu employee target.
- `Waiting`: step berikutnya belum aktif.
- `Approved`: step telah disetujui oleh actor.
- `Rejected`: proses ditolak.
- `Skipped`: step dilewati berdasarkan rule yang valid, misalnya self-approval
  atau threshold nominal.

## Status implementasi saat ini

### Sudah tersedia di backend/web

- [x] Modul web terpisah untuk HR, ESS, finance, approval, payroll, asset,
  procurement, dan administrasi.
- [x] Approval rule berdasarkan departemen, tipe dokumen, employee approver,
  urutan rule, dan threshold yang dikonfigurasi.
- [x] Approval process menyimpan employee target dan actor aktual.
- [x] Web approval memvalidasi employee target pada step aktif.
- [x] Web menyediakan monitoring dan administrasi transaksi ERP utama.
- [x] Dokumentasi web/API dan database baseline tersedia.

### Sudah tersedia di mobile

- [x] Login, logout, dan secure token storage.
- [x] Dashboard personal.
- [x] Absensi clock-in dan clock-out.
- [x] Attendance challenge, geolocation, dan foto.
- [x] Face matching on-device untuk flow absensi.
- [x] Pendaftaran/registrasi wajah on-device jika data wajah belum ada.
- [x] Riwayat absensi.
- [x] Pengajuan cuti.
- [x] Saldo cuti, termasuk tampilan `Unlimited`.
- [x] Pembatalan cuti sebelum tanggal mulai.
- [x] Approval cuti di mobile.
- [x] PIN enam digit pada approval cuti.
- [x] Task list, pembuatan task, perubahan status, dan history.
- [x] Perbaikan bug layout overflow pada form tugas dan ubah status.
- [x] Calendar perusahaan.
- [x] Profile.
- [x] Dashboard Direktur awal:
  - tugas jatuh tempo hari ini;
  - tugas overdue;
  - cuti approved yang berlangsung hari ini;
  - karyawan yang belum memiliki absensi hari ini.
- [x] Backend memvalidasi actor approval terhadap
  `id_karyawan_target` melalui `ApprovalService`.

### P0 - Koreksi model approval lintas web dan mobile

- [x] Hapus role gate dari perhitungan `pending_approvals` di
  `DashboardController`. Count harus berdasarkan:

  ```text
  id_karyawan_target = employee login
  AND status = Pending
  ```

- [x] Pastikan employee yang ditunjuk sebagai approver tetap mendapat badge dan
  inbox approval walaupun tidak memiliki role Manager, HR, atau Direktur.
- [x] Pastikan endpoint approve/reject mencari approval process aktif berdasarkan
  dokumen, employee target, status `Pending`, dan step aktif.
- [x] Pastikan user non-target selalu ditolak walaupun mengetahui ID pengajuan.
- [x] Pastikan pengaju tidak dapat menyetujui pengajuannya sendiri.
- [ ] Pastikan approval yang sudah selesai, ditolak, dibatalkan, atau dilewati
  tidak dapat diproses ulang.
- [ ] Tambahkan test untuk employee approver tanpa role manager.
- [ ] Tambahkan test bahwa employee lain tidak dapat approve request tersebut.
- [ ] Tambahkan test untuk approval rule berbeda antar departemen.
- [ ] Tambahkan test untuk satu approver dan beberapa approver berurutan.
- [x] Audit seluruh endpoint web approval agar tidak memakai role sebagai
  pengganti `id_karyawan_target`.
- [x] Audit seluruh endpoint mobile approval agar tidak memakai role sebagai
  pengganti `id_karyawan_target`.
- [x] Samakan response status, error, catatan, actor, dan timeline antara web
  dan mobile.
- [ ] Pastikan konfigurasi approval rule hanya dapat diubah dari web oleh
  permission yang sesuai, bukan dari mobile.

### P1 - Approval Center web dan mobile

- [ ] Buat Approval Center web untuk seluruh modul approval yang tersedia.
- [x] Buat Approval Center mobile untuk approval yang cocok diproses cepat.
- [ ] Kedua client harus mengambil daftar berdasarkan employee login yang
  tercatat pada `id_karyawan_target`.
- [x] Sumber data inbox harus assignment employee, bukan daftar role.
- [x] Tahap awal yang direkomendasikan untuk endpoint generic:
  - cuti;
  - pengajuan umum;
  - pinjaman;
  - invoice;
  - reimbursement/settlement setelah API siap.
- [ ] Tambahkan filter tipe dokumen, status, tanggal, dan prioritas.
- [x] Tambahkan detail approval API:
  - pengaju;
  - departemen;
  - tanggal;
  - nilai/jumlah;
  - lampiran;
  - employee approver aktif;
  - catatan;
  - status.
- [x] Tambahkan timeline approval API berbasis employee:
  - employee pengaju;
  - employee approver yang ditunjuk pada setiap step;
  - actor dan waktu aksi;
  - step berikutnya jika ada;
  - selesai.
- [x] Jangan menampilkan label seperti “Supervisor”, “Manager”, atau
  “Direktur” sebagai asumsi wajib. Jika label aksi dikonfigurasi, tampilkan
  `label_aksi` dari rule sebagai informasi saja.
- [ ] Kirim notifikasi kepada employee pada `id_karyawan_target`.
- [ ] Tambahkan deep link dari notifikasi ke detail approval.
- [ ] Tambahkan riwayat approval setelah proses selesai.
- [ ] Tambahkan reject reason dan catatan actor.
- [ ] Tambahkan setup, ubah, dan reset PIN approval.
- [ ] Web menyediakan konfigurasi approval rule per departemen dan tipe dokumen.
- [ ] Web menyediakan simulasi/preview rule sebelum rule digunakan.
- [ ] Web menyediakan audit perubahan rule dan employee approver.
- [ ] Mobile tidak menyediakan administrasi rule approval.

### P1 - Employee self-service web dan mobile

- [ ] Detail dan timeline pengajuan cuti.
- [ ] Riwayat pembatalan dan alasan pembatalan.
- [ ] Pengajuan koreksi absensi.
- [ ] Pengajuan dinas luar.
- [ ] Pengajuan lembur jika flow backend ditetapkan.
- [ ] Payslip dan riwayat payroll pribadi.
- [ ] Update profile melalui workflow persetujuan HR.
- [ ] Akses dokumen pribadi dengan permission dan masking.
- [ ] Detail calendar event dan reminder.
- [ ] Web menyediakan detail administratif dan validasi HR.
- [ ] Mobile menyediakan pengajuan, status, dan notifikasi ringkas.

### P1 - Operasional dan task web dan mobile

- [ ] Detail task.
- [ ] Checklist/subtask.
- [ ] Komentar dan mention.
- [ ] Lampiran task.
- [ ] Filter task berdasarkan status, prioritas, due date, dan assignee.
- [ ] Reminder task mendekati due date.
- [ ] Pengajuan reimbursement dengan bukti transaksi.
- [ ] Status pembayaran reimbursement.
- [ ] Web menyediakan pengelolaan task tim, bulk update, reporting, dan
  administrasi.
- [ ] Mobile menyediakan task personal/tim, update status, komentar, dan
  notifikasi.

### P1 - Reliability dan production readiness web dan mobile

- [x] Perbaiki state absensi saat lokasi berhasil tetapi pembuatan challenge
  gagal: lokasi valid dipertahankan dan error ditampilkan sebagai error sesi,
  bukan error lokasi.
- [x] Cegah request lokasi ganda ketika user menekan refresh berulang.
- [x] Bersihkan warning analyzer pada layar absensi, dashboard, dan Approval
  Center yang masuk dalam scope perubahan.
- [ ] Push notification dengan status read/unread.
- [ ] Notification preference.
- [ ] Deep link untuk approval, task, cuti, dan absensi.
- [ ] Loading, empty, error, retry, timeout, dan session-expired state yang
  konsisten.
- [ ] Cache dashboard dan data baca dengan timestamp last sync.
- [ ] Retry upload yang aman dan idempotent.
- [ ] Jangan menganggap approval, clock-in/out, atau submit berhasil hanya
  karena request masuk queue lokal.
- [ ] Auto logout ketika token expired atau dicabut.
- [ ] Biometric/app lock untuk data sensitif.
- [ ] Screenshot/privacy policy untuk halaman sensitif.
- [ ] Crash reporting dan performance monitoring.
- [ ] Web dan mobile memiliki kontrak API/versioning yang terdokumentasi.
- [ ] Web dan mobile menampilkan status transaksi yang konsisten setelah
  refresh atau session baru.
- [ ] Audit log server menjadi sumber utama, bukan log lokal mobile.

### P2 - Fitur tambahan web dan mobile

- [ ] Delegasi approver sementara berbasis employee, masa berlaku, alasan, dan
  audit trail.
- [ ] Acting approver dan escalation berbasis SLA.
- [ ] Reassign approval dengan audit trail approver asli dan pengganti.
- [ ] Approval priority.
- [ ] Bulk approval terbatas dengan konfirmasi dan guard finansial.
- [ ] Purchase request dan procurement ringan.
- [ ] Asset assignment, serah terima, dan scan QR/barcode.
- [ ] Helpdesk/internal service request.
- [ ] Expense advance dan settlement.
- [ ] Sinkronisasi event terpilih ke kalender perangkat.
- [ ] Web menyediakan laporan dan rekonsiliasi lengkap untuk setiap fitur
  finance/operasional.
- [ ] Mobile hanya menerima subset transaksi yang aman dan relevan untuk
  persetujuan atau self-service.

## Matriks fitur web dan mobile

| Area | Web | Mobile | Dasar otorisasi |
|---|---|---|---|
| Login/profile | Lengkap | Self-service | User/session |
| Absensi | Administrasi, koreksi, rekap | Clock-in/out, history | Employee + policy |
| Cuti | Konfigurasi, monitoring, laporan | Request, status, cancel | Employee + approval assignment |
| Approval | Semua modul dan administrasi rule | Approval cepat dan detail ringkas | `id_karyawan_target` |
| Task | Manajemen tim, bulk, reporting | Task personal/tim dan update cepat | Scope employee/permission |
| Calendar | Administrasi event dan filter detail | View dan reminder | Permission/data scope |
| Finance | Transaksi lengkap, posting, laporan | Reimbursement/request/approval terbatas | Permission + assignment |
| Payroll | Proses dan administrasi payroll | Payslip pribadi | Permission/employee |
| Asset | Master, depresiasi, mutasi, audit | Asset yang ditugaskan dan konfirmasi | Permission/employee |
| HR | Master karyawan dan workflow HR | Update data/request pribadi | Permission/employee |
| Notifikasi | Inbox dan audit detail | Push dan quick action | Target employee |
| Approval rule | Create/update/audit | Tidak tersedia | Permission khusus |

## Aturan khusus dashboard

Dashboard boleh role/permission-based karena dashboard adalah fitur monitoring,
bukan otorisasi approval.

- Employee: data personal, absensi, cuti, dan task sendiri.
- Manager/HR/Direktur: tambahan monitoring hanya jika role/permission mengizinkan.
- Pending approval: selalu berdasarkan employee target pada approval process,
  bukan role dashboard.

Dashboard Direktur juga harus mengecualikan dari daftar “belum hadir”:

- employee yang sedang cuti approved;
- hari libur;
- weekend atau hari tanpa jadwal kerja;
- employee nonaktif/resign.

## Test checklist web dan mobile

### Approval

- [ ] Employee yang ditunjuk tanpa role khusus dapat melihat inbox.
- [ ] Employee yang ditunjuk dapat approve dengan PIN valid.
- [ ] Employee yang ditunjuk dapat reject dengan alasan.
- [ ] Employee yang bukan target mendapat 403/422 sesuai kontrak API.
- [ ] Pengaju tidak dapat approve request sendiri.
- [ ] Approval step kedua belum aktif sebelum step pertama selesai.
- [ ] Rule departemen A tidak memengaruhi departemen B.
- [ ] Approval tidak dapat diproses dua kali.
- [ ] Notifikasi hanya dikirim kepada target employee yang relevan.
- [ ] Web dan mobile menampilkan target employee yang sama.
- [ ] Aksi dari web dan mobile menghasilkan status dan audit trail yang sama.
- [ ] Perubahan rule di web hanya memengaruhi pengajuan baru sesuai kebijakan.
- [ ] Rule antar departemen terisolasi pada web dan mobile.

### Dashboard

- [ ] Role Direktur menerima overview direktur.
- [ ] Role lain tidak menerima overview direktur.
- [ ] Employee approver tanpa role manager menerima pending count.
- [ ] Data absent tidak memasukkan employee cuti/libur/nonaktif.

### Mobile

- [ ] PIN invalid tidak mengubah status approval.
- [ ] Session expired diproses dengan logout/re-authentication.
- [ ] Empty, loading, error, dan retry teruji.
- [ ] Flutter unit/widget/integration test mencakup flow utama.

### Web

- [ ] Admin dapat membuat rule employee-based per departemen.
- [ ] Admin dapat melihat preview urutan approval.
- [ ] Approver dapat memproses approval dari web tanpa role khusus jika
  ditunjuk sebagai target.
- [ ] User non-target ditolak pada web.
- [ ] Web menampilkan timeline berdasarkan employee, bukan jabatan.
- [ ] Bulk action tidak melewati validasi setiap approval target.
- [ ] Export/report tidak membocorkan data lintas permission.

## Prioritas kerja berikutnya

1. Hapus role gate pada pending approval.
2. Tambahkan test employee-based approval lintas departemen.
3. Pastikan inbox dan badge mobile berdasarkan assignment employee.
4. Audit dan samakan approval center web dengan aturan employee-based.
5. Bangun Approval Center mobile dari endpoint yang memiliki scope employee.
6. Tambahkan detail dan timeline approval yang tidak mengasumsikan jabatan pada
   web dan mobile.
7. Tambahkan push/in-app notification ke employee target.
8. Perkuat offline mobile, error handling web/mobile, security, dan integration
   test.

## Catatan pembaruan

| Tanggal | Perubahan | Status |
|---|---|---|
| 2026-10-05 | Koreksi model approval: role untuk akses, employee assignment untuk approval | Selesai |
| 2026-10-05 | Roadmap awal mobile dibuat di root backend | Digantikan |
| 2026-10-06 | Roadmap diperluas menjadi web dan mobile | Selesai |
| 2026-10-06 | Perbaikan state lokasi absensi dan warning mobile | Selesai |
| 2026-10-06 | Implementasi fitur registrasi wajah di mobile | Selesai |
| 2026-10-06 | Perbaikan bug bottom overflowed pada modul tugas (mobile) | Selesai |
