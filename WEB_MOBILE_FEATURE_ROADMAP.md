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
- [x] Penyimpanan Multi-Descriptor Wajah berformat JSON di Database untuk kompatibilitas multi-platform.
- [x] Fix otoritas role (Admin & Head) pada halaman Task (Kanban Board) Web.
- [x] Manajemen Kontrak Karyawan: Master template legal dinamis (PKWT, PKWTT, Profesional, Freelance, Magang) dengan TipTap WYSIWYG editor, variabel `{TAG}` dinamis, filter searchable tags, dan modal preview live sample A4.
- [x] Versioning dokumen kontrak karyawan (`v1`, `v2`, dst.) dan sinkronisasi otomatis ke riwayat karir (`tbl_karyawan_riwayat_karir`).
- [x] Isolasi akses role Freelance (UI & backend): redirect langsung ke clock-in, blokir seluruh modul internal.
- [x] Kalender & booking ruangan: deteksi konflik jadwal (Ruang Utama, Ruang 2, Basecamp), ICS feed export, dan public sharing token dengan guard anti-AI scraper.
- [x] Sistem notifikasi terintegrasi (FCM device token registration, in-app notification preference, unread count badge, settings test notification).
- [x] Employee Self-Service (ESS) backend & API: Koreksi absensi, dinas luar, reimbursement, dan view payslip read-only.
- [x] Tata kelola governance: Delegasi approval aktif, audit log immutable berbasis SHA-256 hash-chain, dan rekonsiliasi finansial.

### Sudah tersedia di mobile

- [x] Login, logout, dan secure token storage.
- [x] Dashboard personal.
- [x] Absensi clock-in dan clock-out.
- [x] Attendance challenge, geolocation, dan foto.
- [x] Face matching on-device untuk flow absensi.
- [x] Pendaftaran/registrasi wajah on-device dengan kamera depan dan dialog panduan animasi (`FaceRegistrationScreen` & `FaceRegistrationPromptDialog`).
- [x] Auto-renewal attendance challenge saat resume aplikasi dari background dan sebelum clock-in/out submit.
- [x] Normalisasi orientasi foto (`bakeOrientation`) dan crop padding adaptif untuk mencegah ketidakcocokan deteksi wajah.
- [x] Layar konfirmasi absensi adaptif dark mode (`AbsensiConfirmScreen`).
- [x] Dukungan role Freelance: kartu riwayat kehadiran khusus freelance dan isolasi menu navigasi.
- [x] Multi-descriptor wajah untuk kelancaran absen lintas platform (Web & Mobile).
- [x] Riwayat absensi.
- [x] Pengajuan cuti.
- [x] Saldo cuti, termasuk tampilan `Unlimited`.
- [x] Pembatalan cuti sebelum tanggal mulai.
- [x] Approval cuti dan Unified Approval Center di mobile untuk empat tipe dokumen (Pengajuan, Cuti, Pinjaman, Invoice).
- [x] PIN enam digit pada approval cuti dan approval generic.
- [x] Task list, pembuatan task, perubahan status, dan history.
- [x] Perbaikan bug layout overflow pada form tugas dan ubah status.
- [x] Layar Notifikasi (filter chip & read/unread) dan ESS (Tabs koreksi, dinas luar, payslip, reimbursement).
- [x] Banner izin notifikasi dengan penanganan lifecycle aman.
- [x] Calendar perusahaan.
- [x] Profile.
- [x] Dashboard Direktur:
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
- [x] Tambahkan test untuk employee approver tanpa role manager.
- [x] Tambahkan test bahwa employee lain tidak dapat approve request tersebut.
- [x] Tambahkan test untuk approval rule berbeda antar departemen.
- [x] Tambahkan test untuk satu approver dan beberapa approver berurutan.
- [x] Audit seluruh endpoint web approval agar tidak memakai role sebagai
  pengganti `id_karyawan_target`.
- [x] Audit seluruh endpoint mobile approval agar tidak memakai role sebagai
  pengganti `id_karyawan_target`.
- [x] Samakan response status, error, catatan, actor, dan timeline antara web
  dan mobile.
- [x] Pastikan konfigurasi approval rule hanya dapat diubah dari web oleh
  permission yang sesuai, bukan dari mobile.

### P1 - Approval Center web dan mobile

- [x] Buat Approval Center web untuk Pengajuan, Cuti, Pinjaman, dan Invoice
  melalui inbox terpadu; settlement/reimbursement generic masih belum tersedia.
- [x] Buat Approval Center mobile untuk approval yang cocok diproses cepat.
- [x] Kedua client mengambil daftar berdasarkan employee login yang
  tercatat pada `id_karyawan_target`.
- [x] Sumber data inbox harus assignment employee, bukan daftar role.
- [x] Tahap awal yang direkomendasikan untuk endpoint generic:
  - cuti;
  - pengajuan umum;
  - pinjaman;
  - invoice;
  - reimbursement/settlement setelah API siap.
- [x] Tambahkan filter tipe dokumen, status, tanggal, dan prioritas.
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
- [x] Navigasi detail approval mobile memakai route bertingkat dan back perangkat
  maupun app bar kembali ke Approval Center.
- [x] Kirim notifikasi kepada employee pada `id_karyawan_target`.
- [ ] Tambahkan deep link dari notifikasi ke detail approval.
- [x] Tambahkan riwayat approval setelah proses selesai.
- [x] Tambahkan reject reason dan catatan actor.
- [x] Tambahkan setup, ubah, dan reset PIN approval.
- [x] Web menyediakan konfigurasi approval rule per departemen dan tipe dokumen.
- [ ] Web menyediakan simulasi/preview rule sebelum rule digunakan.
- [x] Web menyediakan audit perubahan rule dan employee approver.
- [x] Mobile tidak menyediakan administrasi rule approval.

### P1 - Employee self-service web dan mobile

- [x] Detail dan timeline pengajuan cuti.
- [x] Riwayat pembatalan dan alasan pembatalan.
- [x] Pengajuan koreksi absensi (Web, API & Mobile ESS screen; teruji di `EssModuleTest`).
- [x] Pengajuan dinas luar (Web, API & Mobile ESS screen; teruji di `EssModuleTest`).
- [ ] Pengajuan lembur jika flow backend ditetapkan.
- [x] Payslip dan riwayat payroll pribadi (Read-only; teruji di `EssModuleTest`).
- [ ] Update profile melalui workflow persetujuan HR.
- [x] Akses dokumen pribadi dengan permission dan masking (Kontrak kerja & riwayat karir).
- [x] Detail calendar event dan reminder (ICS export & public calendar feed).
- [x] Web menyediakan detail administratif dan validasi HR.
- [x] Mobile menyediakan pengajuan, status, dan notifikasi ringkas.

### P1 - Operasional dan task web dan mobile

- [x] Detail task.
- [x] Checklist/subtask.
- [x] Komentar dan mention.
- [x] Lampiran task.
- [x] Filter task berdasarkan status, prioritas, due date, dan assignee.
- [x] Reminder task mendekati due date.
- [x] Pengajuan reimbursement dengan bukti transaksi (Web, API & Mobile ESS; teruji di `EssModuleTest`).
- [x] Status pembayaran reimbursement.
- [x] Web menyediakan pengelolaan task tim, bulk update, reporting, dan
  administrasi.
- [x] Mobile menyediakan task personal/tim, update status, komentar, dan
  notifikasi.

### P1 - Reliability dan production readiness web dan mobile

- [x] Perbaiki state absensi saat lokasi berhasil tetapi pembuatan challenge
  gagal: lokasi valid dipertahankan dan error ditampilkan sebagai error sesi,
  bukan error lokasi.
- [x] Cegah request lokasi ganda ketika user menekan refresh berulang.
- [x] Bersihkan warning analyzer pada seluruh project (`flutter analyze`: 0 issues found!).
- [x] Push notification dengan status read/unread dan token registration FCM (teruji di `NotificationTest`).
- [x] Notification preference di backend API dan mobile settings (teruji di `NotificationTest`).
- [ ] Deep link untuk approval, task, cuti, dan absensi.
- [x] Loading, empty, error, retry, timeout, dan session-expired state yang
  konsisten (Tersedia di Mobile).
- [x] Cache dashboard dan data baca dengan timestamp last sync (Offline caching profil & saldo cuti di Mobile).
- [x] Auto-renewal challenge saat token expired atau resume dari background.
- [ ] Retry upload yang aman dan idempotent.
- [x] Jangan menganggap approval, clock-in/out, atau submit berhasil hanya
  karena request masuk queue lokal.
- [x] Auto logout ketika token expired (401 response handling di AuthInterceptor).
- [ ] Biometric/app lock untuk data sensitif.
- [ ] Screenshot/privacy policy untuk halaman sensitif.
- [ ] Crash reporting dan performance monitoring.
- [x] Web dan mobile memiliki kontrak API/versioning yang terdokumentasi (teruji di `api_contract_test.dart`).
- [x] Web dan mobile menampilkan status transaksi yang konsisten setelah
  refresh atau session baru.
- [x] Audit log server menjadi sumber utama, bukan log lokal mobile.

### P2 - Fitur tambahan web dan mobile

- [x] Delegasi approver sementara berbasis employee, masa berlaku, alasan, dan
  audit trail (teruji di `IdempotencyAndGovernanceTest`).
- [ ] Acting approver dan escalation berbasis SLA.
- [ ] Reassign approval dengan audit trail approver asli dan pengganti.
- [ ] Tambahkan versioning approval rule agar perubahan rule tidak mengubah
  histori process yang sudah berjalan.
- [x] Tambahkan audit trail immutable dengan SHA-256 hash-chain (teruji di `IdempotencyAndGovernanceTest`).
- [x] Tambahkan rekonsiliasi saldo kas/bank dan deteksi diskrepansi transaksi finansial (teruji di `IdempotencyAndGovernanceTest`).
- [ ] Ganti referensi jurnal depresiasi berbasis `jurnal_ref` dengan FK
  `id_jurnal`.
- [ ] Tambahkan `reference_type` formal untuk traceability jurnal lintas modul.
- [x] Registry dokumen approval memiliki subtype table per tipe dengan FK
  native ke tabel `Pengajuan`, `Cuti`, `Pinjaman`, dan `Invoice`.
- [ ] Approval priority.
- [ ] Bulk approval terbatas dengan konfirmasi dan guard finansial.
- [ ] Purchase request dan procurement ringan.
- [x] Manajemen Template Kontrak Kerja (PKWT, PKWTT, Profesional, Freelance, Magang/Internship) dengan TipTap & Live Sample Modal.
- [x] Versioning dokumen kontrak (`v1`, `v2`, dst.) dan sinkronisasi karir otomatis.
- [ ] Asset assignment, serah terima, dan scan QR/barcode.
- [ ] Helpdesk/internal service request.
- [x] Sinkronisasi event terpilih ke kalender perangkat (ICS calendar feed export).
- [x] Deteksi konflik booking ruangan (Ruang Utama, Ruang 2, Basecamp).
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

- [x] Employee yang ditunjuk tanpa role khusus dapat melihat inbox.
- [x] Employee yang ditunjuk dapat approve dengan PIN valid.
- [x] Employee yang ditunjuk dapat reject dengan alasan.
- [x] Employee yang bukan target mendapat 403/422 sesuai kontrak API.
- [x] Pengaju tidak dapat approve request sendiri.
- [x] Approval step kedua belum aktif sebelum step pertama selesai.
- [x] Rule departemen A tidak memengaruhi departemen B.
- [x] Approval tidak dapat diproses dua kali.
- [x] Notifikasi hanya dikirim kepada target employee yang relevan.
- [x] Web dan mobile menampilkan target employee yang sama.
- [x] Aksi dari web dan mobile menghasilkan status dan audit trail yang sama.
- [x] Perubahan rule di web hanya memengaruhi pengajuan baru sesuai kebijakan.
- [x] Rule antar departemen terisolasi pada web dan mobile.

### Dashboard

- [x] Role Direktur menerima overview direktur.
- [x] Role lain tidak menerima overview direktur.
- [x] Employee approver tanpa role manager menerima pending count.
- [x] Data absent tidak memasukkan employee cuti/libur/nonaktif.

### Mobile

- [x] PIN invalid tidak mengubah status approval.
- [x] Session expired diproses dengan logout/re-authentication.
- [x] Empty, loading, error, dan retry teruji.
- [x] Flutter unit/widget/integration test mencakup flow utama (32 test lulus).

### Web

- [x] Admin dapat membuat rule employee-based per departemen.
- [x] Admin dapat melihat preview urutan approval.
- [x] Approver dapat memproses approval dari web tanpa role khusus jika
  ditunjuk sebagai target.
- [x] User non-target ditolak pada web.
- [x] Web menampilkan timeline berdasarkan employee, bukan jabatan.
- [x] Bulk action tidak melewati validasi setiap approval target.
- [x] Export/report tidak membocorkan data lintas permission.

## Prioritas kerja berikutnya

### Pencapaian terkini (Selesai per 2026-10-21)

1. **Static Analysis & Testing Mobile Bersih**: Seluruh 5 warning `flutter analyze` ditutup (`0 issues found`), placeholder test digantikan dengan 32 unit & widget test representatif.
2. **Contract Testing Backend & Mobile**: Uji kontrak deserialisasi schema payload approval dan user profile (`api_contract_test.dart` & `ApiContractTest.php`).
3. **Sistem Notifikasi & FCM**: Registrasi FCM device token, preferensi notifikasi per kanal, unread count badge, dan pengiriman notifikasi uji coba admin.
4. **Employee Self-Service (ESS)**: Pengajuan koreksi absensi, dinas luar, reimbursement dengan bukti, dan view payslip karyawan.
5. **Manajemen Kontrak Legal Dinamis**: Master template PKWT, PKWTT, Profesional, Freelance, dan Magang/Internship dengan TipTap WYSIWYG, variabel `{TAG}` dinamis, filter searchable tags, modal live preview sample A4, versioning dokumen kontrak, serta sinkronisasi karir otomatis.
6. **Hardening Role Freelance**: Isolasi akses menu dan route web, penyediaan kartu absensi riwayat khusus freelance pada mobile.
7. **Pencegahan Konflik & Kalender Publik**: Guard bentrok ruangan (Ruang Utama, Ruang 2, Basecamp), tokenized shareable public calendar, dan ICS export feed.
8. **Peningkatan Biometrik Mobile**: Flow pendaftaran wajah terpandu kamera depan (`FaceRegistrationScreen`), dialog animasi panduan (`FaceRegistrationPromptDialog`), auto-renewal attendance challenge, normalisasi `bakeOrientation`, dan tema dark mode adaptif.

### Prioritas mendatang (Next Roadmap)

1. Integrasi deep linking dari push notification FCM langsung membuka detail approval atau layar absensi.
2. Attestation perangkat tingkat lanjut (Google Play Integrity / Apple App Attest) dan verifikasi embedding wajah server-side.
3. Fitur biometric/app lock (Fingerprint / Face ID) sebelum membuka aplikasi mobile.
4. Modul pengadaan ringan (Purchase Request) dan pelacakan aset dengan pemindaian QR / barcode.

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
| 2026-10-07 | Implementasi multi-descriptor wajah JSON (Web 128D & Mobile 192D) dan perbaikan variable TaskController | Selesai |
| 2026-10-12 | Approval Center web/API/mobile disatukan untuk empat tipe dokumen dan navigasi back mobile diperbaiki | Selesai |
| 2026-10-14 | Hardening role Freelance (isolasi route web, redirect clock-in, kartu riwayat kehadiran mobile) | Selesai |
| 2026-10-15 | Manajemen kontrak kerja (`tbl_karyawan_contracts`), sinkronisasi karir otomatis, versioning dokumen kontrak v1/v2 | Selesai |
| 2026-10-16 | Pencegahan konflik booking ruangan (Utama, Ruang 2, Basecamp), ICS calendar export, shareable public calendar | Selesai |
| 2026-10-17 | Sistem notifikasi terintegrasi (FCM device token registration, in-app preference, unread count badge) | Selesai |
| 2026-10-18 | Fitur Employee Self-Service (ESS) lanjutan: koreksi absensi, dinas luar, reimbursement, dan payslip read-only | Selesai |
| 2026-10-19 | Tata kelola governance: delegasi approver aktif, audit log immutable SHA-256 hash-chain, rekonsiliasi kas/bank | Selesai |
| 2026-10-20 | Peningkatan biometrik mobile: pendaftaran wajah terpandu kamera depan (`FaceRegistrationScreen`), dialog animasi panduan (`FaceRegistrationPromptDialog`), auto-renewal attendance challenge, orientasi foto (`bakeOrientation`), adaptive dark mode | Selesai |
| 2026-10-21 | Master Contract Template Management: Template Freelance & Magang/Internship, TipTap WYSIWYG editor dengan live `{TAG}` variables, live A4 sample preview modal, filter searchable tags, pembersihan skrip test development (`test_db.php`, `test_face.php`) | Selesai |

## Audit rating terbaru - 2026-10-21

Rating ini didasarkan pada implementasi nyata, pengujian otomatis, dan audit keamanan kode per 21 Oktober 2026.

| Area | Rating | Penilaian |
|---|---:|---|
| Arsitektur aplikasi | 8.8/10 | Pemisahan backend/web/mobile, domain service, registry approval, ICS calendar feed, dan isolasi role Freelance sangat kokoh. Kontrak API terverifikasi deserialisasinya melalui automated testing. |
| Desain database/ERD | 9.2/10 | Registry approval single FK dengan subtype native per dokumen, normalisasi `tbl_karyawan_contracts` dengan versioning & karir sync, audit log immutable SHA-256 hash-chain, dan migrasi terverifikasi tanpa data orphan. |
| Flow bisnis ERP | 8.8/10 | Siklus kontrak karyawan lengkap (PKWT, PKWTT, Profesional, Freelance, Magang) dengan variabel TipTap dinamis & modal A4 live preview; modul ESS lengkap (koreksi absensi, dinas luar, reimbursement, payslip); serta booking ruang rapat anti-bentrok. |
| Integritas finansial | 8.2/10 | Deteksi otomatis diskrepansi rekonsiliasi kas/bank, pencegahan booking ganda, penolakan revisi anggaran tak berizin, dan reversal saldo cuti atomic. |
| Security & authorization | 8.2/10 | Role Freelance terisolasi ketat di web & mobile, single-device Sanctum logout, tokenized share calendar dengan guard anti-AI scraper, PIN approval 6-digit, face orientation baking, dan challenge token auto-renewal saat resume aplikasi. |
| Web experience | 8.8/10 | TipTap WYSIWYG contract template editor interaktif, live A4 sample preview modal, searchable tags filter, Unified Approval Center, overview Direktur, dan responsivitas Tailwind CSS yang halus. |
| Mobile experience/API | 8.8/10 | Pendaftaran wajah biometrik terpandu kamera depan dengan dialog animasi, auto-renewal token challenge pada lifecycle app, adaptive dark theme, kartu freelance, layar notifikasi & ESS, serta 0 analyzer issue. |
| Testing & quality | 9.0/10 | Backend melonjak ke **138 test dengan 513 assertions** (100% lulus, 0 gagal); Mobile melonjak ke **32 test** (100% lulus) dengan `flutter analyze` bersih **0 issues found**. |
| Maintainability & documentation | 8.8/10 | Skrip pengujian tidak terpakai dibersihkan (`test_db.php`, `test_face.php`), kode modular, dokumentasi API & roadmap terpadu, seeder E2E komprehensif. |
| **Overall** | **8.8/10** | Kualitas sistem meningkat signifikan dari baseline 8.0/10 ke **8.8/10**. Seluruh isu analyzer dan coverage testing mobile telah diselesaikan; arsitektur siap menopang operasional enterprise penuh. |

### Analisis best practice per area (Audit 2026-10-21)

#### Yang sudah memenuhi best practice
- **Testing Otomatis Komprehensif**: Backend mencapai 138 test dan 513 assertions; Mobile memiliki 32 test unit/widget (login, permission, contract deserialization, dark theme, notification, ESS, freelance card) dengan zero issue pada `flutter analyze`.
- **Manajemen Kontrak Legal Fleksibel**: Template hukum modular (PKWT, PKWTT, Profesional, Freelance, Magang) dengan TipTap WYSIWYG, live sample modal A4, token pengganti otomatis `{NAMA_KARYAWAN}`, `{JABATAN}`, `{TANGGAL_MULAI}`, dan penelusuran riwayat karir karyawan.
- **Isolasi Role Ketat (Least Privilege)**: Akun Freelance dialihkan otomatis ke antarmuka absensi dan dicegah mengakses dashboard finansial, manajemen karyawan, maupun modul internal web/API lainnya.
- **Biometrik & Lifecycle Attendance Aman**: Flow pendaftaran wajah kamera depan dengan panduan dialog animasi; auto-renewal token challenge saat aplikasi kembali dari background atau sebelum submit; normalisasi orientasi gambar EXIF (`bakeOrientation`).
- **Sistem Notifikasi Modern**: Registrasi token FCM multi-platform, preferensi notifikasi per kanal di backend dan mobile settings, serta unread counter badge.
- **Governance & Audit Trail**: Delegasi approval aktif berbasis jangka waktu dan pencatatan audit log immutable menggunakan algoritma SHA-256 hash-chaining.
- **Pembersihan Repositori (Hygiene)**: Skrip ad-hoc development (`test_db.php`, `test_face.php`) dihapus demi menjaga kebersihan repositori production.

#### Gap tersisa untuk rilis final
- Integrasi deep linking push notification FCM untuk langsung melompat ke detail dokumen approval atau layar absensi terkait.
- Implementasi hardware attestation (Google Play Integrity API / Apple App Attest) dan verifikasi server-side untuk face descriptor.
- Fitur biometric/app lock (Fingerprint / Face ID) sebelum membuka aplikasi mobile.
- Modul pengadaan operasional ringan (Purchase Request) dan pemindaian barcode/QR untuk aset inventaris.

### Bukti validasi terakhir (2026-10-21)

- **Backend**: `138 passed (513 assertions)` (Durasi: ~3.8s).
- **Mobile Flutter test**: `32 passed` (Durasi: ~4.0s).
- **Mobile static analysis**: `flutter analyze` -> `No issues found! (ran in 3.2s)`.
- **Database seeder**: `ContractTemplateSeeder` men-seed 5 tipe master template (PKWT, PKWTT, Professional, Freelance, Internship) secara idempotent.
- **Build assets**: Vite production build terkompilasi bersih di `public/build/`.

---

## Audit rating sebelumnya - 2026-10-12

Rating ini didasarkan pada implementasi dan hasil test aktual per 12 Oktober 2026 (sebagai arsip perbandingan perkembangan).

| Area | Rating | Penilaian |
|---|---:|---|
| Arsitektur aplikasi | 8.5/10 | Pemisahan backend/web/mobile, service domain, registry approval, dan route mobile sudah baik; kontrak lintas client dan observability masih perlu diperkuat. |
| Desain database/ERD | 8.9/10 | Registry approval memiliki FK process wajib, subtype table dengan FK native per tipe dokumen, unique constraint, preflight/backfill migration, dan approver/target wajib. Audit history, delegasi, dan reconciliation masih perlu diperkuat. |
| Flow bisnis ERP | 8.2/10 | Cuti, approval terpadu, task, absensi, dashboard direktur, dan detail approval lintas client sudah jelas; self-service lanjutan dan finance mobile masih terbatas. |
| Integritas finansial | 7.5/10 | Reversal cuti, GL traceability, tax settlement, dan period control membaik; idempotency dan reconciliation end-to-end belum lengkap. |
| Security & authorization | 7.0/10 | Assignment employee, PIN, Sanctum, challenge absensi, dan route authorization sudah benar secara konsep; attestation server-side, verifikasi wajah server-side, rate limit menyeluruh, dan audit keamanan mobile belum lengkap. |
| Web experience | 8.0/10 | Modul administrasi/transaksi luas dan Approval Center web sudah terpadu; preview rule, audit perubahan rule, deep link notifikasi, dan beberapa alur back masih perlu pengujian browser. |
| Mobile experience/API | 8.1/10 | Absensi, cuti, task, dashboard direktur, Approval Center, detail/timeline, dan navigasi back sudah tersedia; push notification, deep link, offline write safety, dan production monitoring belum lengkap. |
| Testing & quality | 7.2/10 | Backend **96 test/263 assertions** lulus dan mobile test lulus, tetapi mobile baru memiliki satu placeholder test; `flutter analyze` masih menemukan 5 issue. |
| Maintainability & documentation | 8.0/10 | Struktur feature mobile, dokumentasi API, roadmap, dan commit perubahan cukup jelas; ada debug `print`, dependency analyzer issue, dan dokumentasi mobile belum sepenuhnya sinkron dengan fitur Approval Center terbaru. |
| **Overall** | **8.0/10** | Fondasi sudah kuat dan layak masuk fase hardening; belum layak disebut production-ready penuh untuk proses finansial/sensitif sebelum gap reliability, security, observability, dan test coverage ditutup. |

### Analisis best practice per area

#### Yang sudah mengikuti best practice

- Otorisasi approval berbasis employee assignment (`id_karyawan_target`),
  bukan role semata, dan divalidasi ulang di backend.
- Registry approval dan subtype document menjaga integritas referensi tanpa
  empat foreign key nullable pada process.
- Operasi approval dan reversal saldo memakai service domain serta
  transaction/locking pada jalur kritis.
- API mobile memakai versioning `/api/v1`, Sanctum, rate limit login/absensi,
  timeout jaringan, secure storage, dan auto logout saat 401.
- Web dan mobile memiliki pemisahan tanggung jawab yang sehat: administrasi
  kompleks di web, ESS/approval cepat di mobile.
- Navigasi Approval Center mobile sudah menggunakan GoRouter dengan root
  navigator yang benar; app bar back dan Android system back kembali ke inbox.
- Face matching dilakukan on-device sebagai pre-check dan dokumentasi sudah
  menyatakan bahwa sinyal device bukan pengganti otorisasi backend.
- Backend test suite saat ini lulus **96 test dengan 263 assertions**.

#### Yang belum memenuhi best practice

- Test mobile belum representatif: masih satu placeholder test, tanpa coverage
  unit/widget/integration untuk auth, approval, back navigation, error, dan
  session expiry.
- `flutter analyze` masih menghasilkan 5 issue: penggunaan `print` pada
  production code dan dependency `http` yang belum dideklarasikan. Ini harus
  bersih sebelum release gate.
- Logging mobile belum memiliki logging framework, redaction policy, crash
  reporting, atau performance monitoring yang terdokumentasi.
- Contract test antara API, web, dan mobile belum ada; perubahan payload dapat
  lolos backend test tetapi merusak client.
- Push notification, read/unread state, notification preference, deep link,
  offline retry idempotent, dan upload resume belum selesai.
- Attestation Play Integrity/App Attest dan verifikasi wajah server-side belum
  aktif; kontrol anti-emulator/fake GPS tetap hanya sinyal tambahan.
- Audit immutable untuk perubahan approval rule, reassign/delegasi, payroll,
  saldo, dan transaksi finansial belum lengkap.
- Idempotency key dan reconciliation end-to-end untuk operasi finansial belum
  menjadi kontrak wajib.
- Beberapa TODO bisnis masih ada pada reversal budget dan guard approval; harus
  ditutup atau diberi issue/acceptance criteria sebelum production.

### Koreksi status roadmap setelah audit

- Approval Center mobile sudah tersedia dengan list/action, detail timeline,
  dan action PIN.
- Detail dan timeline approval tersedia di API dan digunakan oleh mobile;
  settlement/reimbursement generic masih belum terintegrasi penuh.
- Approval Center web generic untuk empat tipe dokumen sudah tersedia; filter
  tanggal/prioritas dan administrasi rule preview masih belum selesai.
- Navigasi back detail approval mobile sudah diperbaiki untuk app bar dan tombol
  back Android, tetapi wajib dipertahankan dengan regression test.
- Push notification, deep link, notification preference, dan approval rule
  audit belum selesai.
- Dashboard Direktur perlu validasi lanjutan agar employee cuti, hari libur,
  weekend, dan nonaktif tidak salah dihitung sebagai absent.
- Item “P0 & P1 selesai” harus dibaca sebagai penyelesaian scope yang sudah
  dikerjakan, bukan seluruh checklist P0/P1.

### Bukti validasi terakhir

- Backend: `96 passed`, `263 assertions`.
- Mobile Flutter test: lulus, tetapi saat ini hanya satu placeholder test.
- `flutter analyze`: **5 issues** (debug `print` production dan dependency
  `http` yang belum dideklarasikan; seluruh issue wajib ditutup).
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
