# Testing

## Backend

```bash
php artisan test
```

Suite baseline saat ini (2026-10-21): **138 test dan 513 assertions** berhasil (100% pass, 0 failure).

Test penting mencakup:
- Approval workflow & Unified Approval Center (`CutiFlowTest`, `UnifiedApprovalCenterTest`).
- Manajemen template & versioning kontrak (`ContractTemplateTest`, `ContractLifecycleIntegrityTest`).
- Isolasi role Freelance web & API (`FreelanceRoleAccessTest`).
- Deteksi konflik ruang rapat & public calendar (`PublicCalendarAndConflictTest`).
- Modul ESS: koreksi absensi, dinas luar, payslip, dan reimbursement (`EssModuleTest`).
- Tata kelola hash-chain immutable audit log, delegasi, dan rekonsiliasi (`IdempotencyAndGovernanceTest`).
- Notifikasi & FCM device token (`NotificationTest`).
- Hari libur & Google Calendar sync fallback (`HariLiburTest`).

## Database

```bash
php artisan migrate:fresh
php artisan migrate:status
```

`migrate:fresh` hanya untuk lokal/testing dengan data disposable.

## Mobile

```bash
flutter test
flutter analyze
```

- Suite unit & widget test mobile (2026-10-21): **32 test passed** (100% pass).
- Analisis statis: `flutter analyze` menghasilkan **No issues found!** (0 error, 0 warning, 0 lint issue).
- Test mencakup form login, dialog permission lokasi, kontrak API deserialisasi (`UserModel`), render profile screen (Light & Dark theme), penanganan izin notifikasi, tabs ESS, dan freelance attendance card.

## Manual End-to-End (E2E) Testing (Web UI)

Untuk menguji workflow sistem secara menyeluruh dari Web UI (terutama fungsionalitas approval dan akses *role*), Anda dapat menggunakan `E2ETestingSeeder`. Seeder ini akan menyuntikkan data dummy yang komprehensif, termasuk struktur departemen, *approval rules*, pengguna (*Direktur*, *Finance*, *HR*, *Staf*), serta berbagai skenario pengajuan (Dana, Cuti, Pinjaman, dan Invoice) yang saling berkaitan.

Jalankan perintah berikut:
```bash
php artisan db:seed --class=E2ETestingSeeder
```

### Akun Pengujian
Gunakan kredensial berikut untuk *login* ke Web UI (password untuk semua akun adalah `password`):

1. **Super Admin** (Akses Semua Fitur & Settings): `test.admin@persija.id`
2. **Direktur** (sebagai Approver Tertinggi): `test.direktur@persija.id`
3. **Finance** (sebagai Approver Keuangan): `test.finance@persija.id`
4. **HR** (sebagai Approver Cuti): `test.hr@persija.id`
5. **Staf 1** (Mengajukan Dana Operasional & Pinjaman): `test.staf1@persija.id`
6. **Staf 2** (Mengajukan Cuti Tahunan): `test.staf2@persija.id`
7. **Freelance** (Hanya Akses Absensi di Web & Mobile): `test.freelance@persija.id`

### Skenario Uji (Tertunda / Pending Approval)
Setelah seeder dijalankan, terdapat beberapa skenario pengajuan yang siap untuk di-approve:
- **Pengajuan Dana:** Diajukan oleh `Staf 1`, menunggu approval dari `Direktur` (Lvl 1) & `Finance` (Lvl 2).
- **Cuti Tahunan:** Diajukan oleh `Staf 2`, menunggu approval dari `HR` (Lvl 1) & `Direktur` (Lvl 2).
- **Pinjaman:** Diajukan oleh `Staf 1`, menunggu approval dari `Direktur`.
- **Invoice:** Telah di-*approve* otomatis oleh Finance dan saat ini dalam status `Draft/Pending Approval` (menunggu finalisasi/approval oleh `Direktur`).

Gunakan akun-akun di atas untuk memastikan *dashboard* menampilkan notifikasi dan dokumen yang sesuai dengan wewenang *role* masing-masing.
