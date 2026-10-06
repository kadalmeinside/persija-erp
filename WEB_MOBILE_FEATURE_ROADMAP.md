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
- [x] Dashboard Direktur web (Overview tugas, cuti, dan absensi).
- [x] Akses tugas untuk Direktur mencakup semua tugas karyawan (mobile & web).
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
- [x] Pastikan approval yang sudah selesai, ditolak, dibatalkan, atau dilewati
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
  konsisten (Sebagian selesai di Mobile).
- [x] Cache dashboard dan data baca dengan timestamp last sync (Offline caching profil & saldo cuti di Mobile).
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
- [ ] Tambahkan versioning approval rule agar perubahan rule tidak mengubah
  histori process yang sudah berjalan.
- [ ] Tambahkan audit trail immutable untuk perubahan rule, target, status,
  saldo, invoice, payroll, dan koreksi absensi.
- [ ] Tambahkan rekonsiliasi saldo kas/bank dan idempotency transaksi finansial.
- [ ] Ganti referensi jurnal depresiasi berbasis `jurnal_ref` dengan FK
  `id_jurnal`.
- [ ] Tambahkan `reference_type` formal untuk traceability jurnal lintas modul.
- [x] Registry dokumen approval memiliki subtype table per tipe dengan FK
  native ke tabel `Pengajuan`, `Cuti`, `Pinjaman`, dan `Invoice`.
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

1. Tambahkan test employee-based approval lintas departemen.
2. Tambahkan test bahwa employee lain tidak dapat approve request tersebut.
3. Bangun Approval Center web yang tersinkronisasi penuh dengan versi mobile.
4. Tambahkan push/in-app notification ke employee target menggunakan Firebase Cloud Messaging (FCM).
5. Kembangkan modul Employee Self-Service lanjutan (Riwayat Pembatalan Cuti, Koreksi Absensi).
6. Tambahkan deep link dari notifikasi ke detail approval.
7. Bangun modul Payslip untuk Mobile (Read-only) dan pengajuan dinas luar.

## Scalability dan migration-safety

### Kondisi saat ini

- `tbl_approval_process` memiliki satu FK wajib
  `id_approval_document`; empat FK dokumen nullable sudah dihapus.
- `tbl_approval_documents` memiliki unique `(document_type, document_id)`,
  subtype table per tipe, dan menjadi registry tunggal untuk timeline/process.
- `id_karyawan_target` dan `id_karyawan_approver` wajib.
- Approval process memakai row locking pada action sehingga concurrent approval
  tidak boleh memproses step yang sama dua kali.
- Pengembangan fitur baru dapat menambah `document_type` dan resolver tanpa
  menambah kolom nullable baru pada process.

### Batas integritas yang masih ada

`document_id` pada registry adalah nilai denormalisasi untuk query cepat, tetapi
integritas referensi dijaga oleh subtype table berikut:

- `tbl_approval_document_pengajuan`;
- `tbl_approval_document_cuti`;
- `tbl_approval_document_pinjaman`;
- `tbl_approval_document_invoice`.

Masing-masing subtype memiliki primary key ke registry dan foreign key native ke
tabel dokumen. Service membuat atau mengambil registry dan subtype secara
idempotent dalam flow approval.

### Aturan perubahan agar tidak merusak integrity

Semua perubahan schema berikutnya wajib mengikuti pola:

1. **Expand**: tambah tabel/kolom/constraint baru tanpa menghapus kontrak aktif.
2. **Backfill atau fresh reset**: validasi semua row development dan hentikan
   migration jika ada data invalid; jangan memakai silent fallback.
3. **Switch**: pindahkan service, controller, query, UI, dan test ke struktur
   baru dalam satu kontrak terverifikasi.
4. **Validate**: jalankan migration fresh, backend test, mobile test, dan
   `git diff --check`.
5. **Contract**: hanya setelah seluruh consumer berpindah, hapus kolom atau
   tabel legacy.

Pada fase development saat ini, `migrate:fresh --seed` adalah jalur reset yang
disetujui. Untuk production nanti, migration destructive tidak boleh dipakai
tanpa backup, preflight validation, dan rollback plan.

## Catatan pembaruan

| Tanggal | Perubahan | Status |
|---|---|---|
| 2026-10-05 | Koreksi model approval: role untuk akses, employee assignment untuk approval | Selesai |
| 2026-10-05 | Roadmap awal mobile dibuat di root backend | Digantikan |
| 2026-10-06 | Roadmap diperluas menjadi web dan mobile | Selesai |
| 2026-10-06 | Perbaikan state lokasi absensi dan warning mobile | Selesai |
| 2026-10-06 | Implementasi fitur registrasi wajah di mobile | Selesai |
| 2026-10-06 | Perbaikan bug bottom overflowed pada modul tugas (mobile) | Selesai |
| 2026-10-06 | Implementasi Dashboard/Ringkasan Direktur di mobile dan web | Selesai |
| 2026-10-06 | Penyelesaian P0 & P1 Hasil Audit (Keamanan, Biometrik, Bug Approval, Caching, UX) | Selesai |

## Audit rating terbaru - 2026-10-06

Rating ini didasarkan pada implementasi dan hasil test aktual, bukan hanya
checkbox roadmap.

| Area | Rating | Penilaian |
|---|---:|---|
| Arsitektur aplikasi | 8.3/10 | Pemisahan backend/web/mobile dan service approval sudah baik; generic approval masih perlu disatukan di web. |
| Desain database/ERD | 8.9/10 | Registry approval memiliki FK process wajib, subtype table dengan FK native per tipe dokumen, unique constraint, preflight/backfill migration, dan approver/target wajib. Audit history, delegasi, dan reconciliation masih perlu diperkuat. |
| Flow bisnis ERP | 8.0/10 | Cuti, approval, task, absensi, dan dashboard direktur sudah memiliki alur yang jelas; self-service dan finance mobile masih terbatas. |
| Integritas finansial | 7.5/10 | Reversal cuti, GL traceability, tax settlement, dan period control membaik; idempotency dan reconciliation end-to-end belum lengkap. |
| Security & authorization | 7.2/10 | Employee-target approval, PIN, challenge, dan route authorization sudah lebih kuat; attestation backend dan server-side face verification masih tertunda. |
| Web experience | 7.7/10 | Modul administrasi dan transaksi web luas; Approval Center generic, preview rule, dan audit perubahan rule belum selesai. |
| Mobile experience/API | 7.8/10 | Absensi, cuti, task, dashboard direktur, Approval Center, dan timeline approval sudah tersedia; notification, deep link, dan offline operation belum lengkap. |
| Testing & quality | 7.8/10 | Backend 95 test/253 assertion dan mobile test lulus; coverage generic approval lintas departemen dan UI masih perlu ditambah. |
| Maintainability & documentation | 7.9/10 | Roadmap dan kontrak API membaik; beberapa item roadmap masih perlu dikoreksi agar hanya menandai implementasi yang benar-benar terverifikasi. |
| **Overall** | **8.1/10** | Layak masuk fase hardening dan perluasan fitur; belum mencapai financial-grade production readiness penuh. |

### Koreksi status roadmap setelah audit

- Approval Center mobile sudah tersedia dengan list/action, detail timeline,
  dan action PIN.
- Detail dan timeline approval sudah tersedia di API, bukan berarti seluruh
  implementasi web dan mobile telah selesai.
- Approval Center web generic lintas modul belum selesai.
- Push notification, deep link, notification preference, dan approval rule
  audit belum selesai.
- Dashboard Direktur perlu validasi lanjutan agar employee cuti, hari libur,
  weekend, dan nonaktif tidak salah dihitung sebagai absent.
- Item “P0 & P1 selesai” harus dibaca sebagai penyelesaian scope yang sudah
  dikerjakan, bukan seluruh checklist P0/P1.

### Bukti validasi terakhir

- Backend: `95 passed`, `253 assertions`.
- Mobile Flutter test: lulus.
- Dart analyzer scoped (`absensi`, `approvals`, `dashboard`): `No issues found`.
- Regresi `InvoiceStatus::Approved` pada generic approval action diperbaiki.
- Constraint approval diperketat melalui migration
  `2026_10_06_222500_harden_approval_integrity`.
- Rule approval tidak boleh memiliki approver nullable; process tidak boleh
  memiliki target nullable atau lebih dari satu dokumen sumber.
- Struktur dokumen approval dinormalisasi melalui
  `tbl_approval_documents`; `tbl_approval_process` sekarang hanya memiliki
  `id_approval_document` sebagai FK wajib, tanpa empat FK dokumen nullable.
- Data development dianggap disposable dan schema divalidasi melalui
  `migrate:fresh --seed`; tidak ada kompatibilitas legacy yang mengorbankan
  integritas schema.
- Migration normalisasi memiliki backfill legacy dan preflight: process lama
  harus memiliki tepat satu referensi dokumen; row ambigu atau orphan
  menghentikan migration secara eksplisit.
- Setelah baseline normalisasi ini, perubahan berikutnya wajib menggunakan
  migration incremental; `migrate:fresh` hanya untuk reset development.
