# 08 — Testing

## 1. Struktur Test

```
tests/
├── Feature/
│   ├── ApprovalServiceTest.php       # ✅ 17 test cases — ApprovalService lengkap
│   ├── PengajuanFlowTest.php         # ✅ 14+ test cases — Flow pengajuan end-to-end
│   ├── PengajuanRevisionFixTest.php  # ✅ 4 test cases — Bug fix revision workflow
│   ├── PengajuanRevisionFlowTest.php # ✅ Flow revision terintegrasi
│   ├── CutiFlowTest.php              # ✅ Flow cuti end-to-end
│   ├── RoleAccessTest.php            # ✅ 8+ test cases — Kontrol akses per role
│   ├── ProfileTest.php               # ✅ Update profil user
│   └── Auth/                         # ✅ Autentikasi
└── Unit/                             # (minimal saat ini)
```

---

## 2. Menjalankan Tests

```bash
# Semua test
php artisan test

# Test spesifik (satu file)
php artisan test tests/Feature/ApprovalServiceTest.php

# Test spesifik dengan filter nama
php artisan test --filter="identity_guard_blocks_wrong_approver"

# Dengan coverage (butuh Xdebug)
php artisan test --coverage
```

> **Catatan:** Test menggunakan `RefreshDatabase` — database di-reset per test class. Wajib menggunakan SQLite in-memory atau database test terpisah.

---

## 3. `ApprovalServiceTest` — Katalog Test Cases

### Group: initApproval

| Test | Deskripsi | Verifikasi |
|---|---|---|
| `init_approval_creates_pending_process_for_first_level` | Rule 1 level → step level 1 berstatus Pending | DB record status='Pending' |
| `init_approval_sets_subsequent_levels_to_waiting` | Rule 2 level → level 2 berstatus Waiting | DB record status='Waiting' |
| `init_approval_sets_pengajuan_status_to_pending_approval` | Setelah init → status global = PENDING_APPROVAL | model->refresh() |
| `init_approval_does_nothing_when_no_rules_exist` | Tanpa rule → tidak ada record process | assertDatabaseCount = 0 |

### Group: approve (single level)

| Test | Deskripsi | Verifikasi |
|---|---|---|
| `approve_single_level_sets_pengajuan_to_approved` | 1 level, approved → status APPROVED | model->refresh() |
| `approve_first_level_advances_to_second_level` | 2 level, L1 approve → L2 jadi Pending | DB L2 status='Pending', global masih PENDING_APPROVAL |
| `approve_all_levels_sets_pengajuan_to_approved` | 2 level, keduanya approve → APPROVED | model->refresh() |
| `approve_step_records_uuid` | Langkah approve → UUID terisi | assertNotNull($step->uuid) |

### Group: reject

| Test | Deskripsi | Verifikasi |
|---|---|---|
| `reject_sets_pengajuan_to_rejected` | Reject → status REJECTED | model->refresh() |
| `reject_updates_the_step_to_rejected` | Step menjadi Rejected | DB status='Rejected' |
| `approve_throws_if_no_pending_step_exists` | Approval tanpa init → Exception | expectException |

### Group: Segregation of Duties (Auto-Skip + Identity Guard)

| Test | Deskripsi | Verifikasi |
|---|---|---|
| `self_approver_step_is_skipped_during_init` | Approver = Pengaju → status='Skipped' + auto-approved | DB Skipped + global=APPROVED |
| `self_approver_is_skipped_but_next_valid_approver_is_activated` | L1 skip, L2 valid → L2 langsung Pending | DB L1=Skipped, L2=Pending |
| `identity_guard_blocks_wrong_approver` | Approver salah → Exception "tidak berwenang" | expectException |
| `identity_guard_allows_correct_approver` | Approver benar → Approved | tidak ada exception |

### Group: revision

| Test | Deskripsi | Verifikasi |
|---|---|---|
| `revision_sets_pengajuan_status_to_revision` | Request revision → status REVISION | model->refresh() |
| `revision_updates_step_status_to_revision` | Step menjadi Revision + catatan tersimpan | DB status='Revision', catatan |
| `revision_identity_guard_blocks_wrong_approver` | Orang salah minta revisi → Exception | expectException |
| `revision_throws_if_no_pending_step` | Revision tanpa init → Exception | expectException |

### Group: Pinjaman

| Test | Deskripsi | Verifikasi |
|---|---|---|
| `pinjaman_init_approval_creates_pending_process` | Init approval pinjaman → step Pending | DB id_pinjaman + status='Pending' |
| `pinjaman_approve_sets_status_to_approved_and_triggers_payment_request` | Final approval → APPROVED + auto-buat PengajuanHeader | assertDatabaseHas pengajuan_header |
| `pinjaman_reject_sets_status_to_rejected` | Reject pinjaman → REJECTED | model->refresh() |

---

## 4. `PengajuanFlowTest` — Katalog Test Cases

| Test | Deskripsi |
|---|---|
| `finance_can_see_all_pengajuan` | Role Finance dapat akses index |
| `employee_redirected_to_my_requests` | Karyawan biasa diarahkan ke my-requests |
| `can_create_pengajuan_with_budget` | Pengajuan berhasil dibuat dengan budget cukup |
| `cannot_create_pengajuan_without_budget` | Tanpa budget → gagal |
| `finance_can_pay_approved_pengajuan` | Finance bisa catat pembayaran jika APPROVED |
| `cannot_pay_unapproved_pengajuan` | Tidak bisa bayar jika belum APPROVED |
| `pengaju_can_cancel_draft` | Pengaju bisa cancel pengajuan sendiri |
| `cannot_cancel_paid_pengajuan` | Tidak bisa cancel yang sudah PAID |

---

## 5. `RoleAccessTest` — Katalog Test Cases

| Test | Deskripsi |
|---|---|
| `super_admin_can_access_users_management` | Super Admin → halaman users |
| `non_admin_cannot_access_users_management` | Karyawan biasa → 403 |
| `hr_staff_can_access_karyawan_management` | HR Staff → halaman karyawan |
| `finance_can_access_budget` | Finance → halaman budget |
| `non_finance_cannot_access_budget` | Karyawan biasa → 403 |
| `all_roles_can_access_pengajuan_my_requests` | Semua role → my-requests |
| `all_roles_can_access_calendar` | Semua role → kalender |

---

## 6. `CutiFlowTest` — Katalog Test Cases

| Test | Deskripsi |
|---|---|
| `employee_can_submit_cuti` | Karyawan bisa ajukan cuti |
| `cuti_goes_through_approval_flow` | Cuti masuk ke workflow approval |
| `approved_cuti_deducts_leave_balance` | Saldo cuti berkurang setelah approve |
| `rejected_cuti_does_not_deduct_balance` | Saldo tidak berubah jika ditolak |

---

## 7. Best Practices Testing

### Setup Pattern (tiap test file)

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Buat dept, karyawan, user minimal di sini
        // JANGAN buat di method test individual — duplikasi berlebihan
    }
}
```

### Factory vs Manual Create

- **Gunakan `User::factory()->create()`** untuk buat user dengan password hash
- **Gunakan `Model::create([...])` manual** untuk model yang tidak punya factory terdefinisi (Karyawan, Departemen, dll)

### Helper Privat yang Direkomendasikan

```php
private function makeApprovedPengajuan(): PengajuanHeader
{
    // Buat + init + approve langsung
}
```

---

## 8. Coverage Status (Estimasi)

| Komponen | Coverage | Keterangan |
|---|---|---|
| ApprovalService | ~95% | Sangat lengkap |
| PengajuanService (create/update/cancel) | ~70% | Flow utama tercakup |
| Role Access Control | ~80% | Semua role utama dicek |
| Cuti Flow | ~75% | Termasuk deduct saldo |
| InvoiceService | ❌ Belum | Belum ada test file |
| PayrollService | ❌ Belum | Belum ada test file |
| PinjamanService | ~60% | Via ApprovalServiceTest |
| BudgetCheckService | ~50% | Via PengajuanFlowTest |
