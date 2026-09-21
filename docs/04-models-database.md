# 04 — Model & Database

## 1. Daftar Tabel Utama

| Tabel | Model | Deskripsi |
|---|---|---|
| `users` | `User` | Akun login sistem |
| `tbl_karyawan` | `Karyawan` | Data karyawan |
| `tbl_departemen` | `Departemen` | Master departemen |
| `tbl_pengajuan_header` | `PengajuanHeader` | Header pengajuan dana |
| `tbl_pengajuan_detail` | `PengajuanDetail` | Rincian item pengajuan |
| `tbl_pengajuan_pembayaran` | `PengajuanPembayaran` | Pembayaran atas pengajuan |
| `tbl_approval_process` | `ApprovalProcess` | Langkah-langkah persetujuan |
| `tbl_approval_rules` | `ApprovalRule` | Aturan persetujuan per departemen |
| `tbl_pinjaman` | `Pinjaman` | Pengajuan pinjaman karyawan |
| `tbl_angsuran_pinjaman` | `AngsuranPinjaman` | Jadwal cicilan pinjaman |
| `tbl_pengajuan_cuti` | `PengajuanCuti` | Pengajuan cuti karyawan |
| `tbl_saldo_cuti` | `SaldoCuti` | Saldo cuti per karyawan per periode |
| `tbl_jenis_cuti` | `JenisCuti` | Master jenis cuti |
| `tbl_payroll` | `Payroll` | Header penggajian |
| `tbl_payroll_header` | `PayrollHeader` | Header payroll tambahan |
| `tbl_payroll_detail` | `PayrollDetail` | Detail gaji per karyawan per item |
| `tbl_akun_gl` | `AkunGl` | Chart of Accounts (CoA) |
| `tbl_jurnal_header` | `JurnalHeader` | Jurnal akuntansi |
| `tbl_jurnal_detail` | `JurnalDetail` | Detail entri jurnal |
| `tbl_kas_bank` | `KasBank` | Master kas/bank perusahaan |
| `tbl_invoice_header` | `InvoiceHeader` | Invoice penjualan |
| `tbl_invoice_detail` | `InvoiceDetail` | Detail item invoice |
| `tbl_penerimaan_pembayaran` | `PenerimaanPembayaran` | Pembayaran masuk invoice |
| `tbl_vendor` | `Vendor` | Master vendor/supplier |
| `tbl_rekening_bank` | `RekeningBank` | Rekening bank (polimorfik: Vendor/Karyawan) |
| `tbl_budget_master` | `BudgetMaster` | Master anggaran |
| `tbl_pos_anggaran` | `PosAnggaran` | Pos anggaran (program + akun) |
| `tbl_program_kerja` | `ProgramKerja` | Program kerja per departemen |
| `tbl_periode_anggaran` | `PeriodeAnggaran` | Periode/tahun anggaran |
| `tbl_aset` | `Aset` | Aset tetap |
| `tbl_aset_master` | `AsetMaster` | Master kategori aset |
| `tbl_aset_depresiasi_log` | `AsetDepresiasiLog` | Log penyusutan aset |
| `tbl_tax_type` | `TaxType` | Master jenis pajak |
| `tbl_laporan_penggunaan` | `LaporanPenggunaan` | Settlement/laporan uang muka |
| `tbl_laporan_detail` | `LaporanDetail` | Detail item settlement |
| `tbl_log_persetujuan` | `LogPersetujuan` | Log historis persetujuan |
| `tbl_hari_libur` | `HariLibur` | Master hari libur nasional |
| `tbl_company_events` | `CompanyEvent` | Event perusahaan |
| `tickets` | `Ticket` | Tiket IT Support |
| `ticket_comments` | `TicketComment` | Komentar tiket |
| `settings` | `Setting` | Konfigurasi sistem (key-value) |
| `notifications` | *(Bawaan Laravel)* | Notifikasi database |
| `activity_log` | *(Spatie)* | Audit trail semua perubahan |

---

## 2. Diagram Relasi Inti

### 2.1 Pengajuan Dana

```
PengajuanHeader (id)
├── belongsTo Karyawan (id_pengaju)        → pembuat pengajuan
├── belongsTo Karyawan (id_karyawan_penerima) → penerima uang
├── belongsTo Departemen
├── belongsTo Vendor (id_vendor_penerima)
├── hasMany PengajuanDetail               → rincian item
├── hasMany ApprovalProcess               → langkah-langkah approval
├── hasOne  ApprovalProcess [currentStep] → langkah aktif (status=Pending)
├── hasMany PengajuanPembayaran           → pembayaran atas pengajuan
└── hasOne  LaporanPenggunaan             → settlement (UangMuka saja)

PengajuanDetail
├── belongsTo PengajuanHeader
├── belongsTo AkunGl           → akun beban
├── belongsTo ProgramKerja     → program kerja
└── belongsTo TaxType          → jenis pajak (nullable)
```

### 2.2 Approval

```
ApprovalProcess
├── belongsTo PengajuanHeader (id_pengajuan) [nullable]
├── belongsTo PengajuanCuti   (id_cuti)      [nullable]
├── belongsTo Pinjaman        (id_pinjaman)  [nullable]
├── belongsTo Karyawan (id_karyawan_target)  → approver yg ditunjuk
└── belongsTo Karyawan (id_karyawan_action)  → approver yg eksekusi

ApprovalRule
├── belongsTo Departemen
└── belongsTo Karyawan (id_karyawan_approver)
```

### 2.3 Karyawan

```
Karyawan (tbl_karyawan)
├── belongsTo User
├── belongsTo Departemen
├── hasMany PengajuanHeader [sebagai pengaju]
├── hasMany PengajuanHeader [sebagai penerima]
├── hasMany Pinjaman
├── hasMany PengajuanCuti
├── hasMany SaldoCuti
├── morphMany RekeningBank (owner_type='App\Models\Karyawan')
└── hasMany PayrollDetail
```

### 2.4 Keuangan & GL

```
JurnalHeader (tbl_jurnal_header)
└── hasMany JurnalDetail → masing-masing ke AkunGl

InvoiceHeader
├── belongsTo Departemen
├── belongsTo Pelanggan
├── hasMany InvoiceDetail → masing-masing ke AkunGl (akun pendapatan)
└── hasMany PenerimaanPembayaran

BudgetMaster
├── belongsTo PeriodeAnggaran
└── belongsTo PosAnggaran
      ├── belongsTo ProgramKerja → belongsTo Departemen
      └── belongsTo AkunGl
```

---

## 3. Model-model Penting

### 3.1 `PengajuanHeader`

**File:** `app/Models/PengajuanHeader.php`

```php
protected $casts = [
    'tgl_pengajuan'          => 'date',
    'total_nominal_diajukan' => 'decimal:2',
    'status_global'          => PengajuanStatus::class, // PHP Enum cast
    'metode_pembayaran'      => PaymentMethod::class,
];
```

**Computed Attributes (Accessor):**

| Attribute | Formula | Deskripsi |
|---|---|---|
| `total_dibayar` | `SUM(pembayaran.nominal_bayar)` | Total yang sudah dibayarkan |
| `sisa_tagihan` | `total_nominal - total_dibayar` | Sisa kewajiban |
| `status_pembayaran` | `Unpaid / Partial / Paid` | Status pembayaran singkat |

> **Catatan N+1:** `total_dibayar` sudah dioptimasi — menggunakan collection sum jika relasi sudah di-eager load, atau withSum jika diset lewat query.

**Activity Log:** Model ini menggunakan `Spatie\Activitylog\Traits\LogsActivity`. Setiap perubahan field yang `fillable` akan tercatat otomatis di `activity_log`.

---

### 3.2 `ApprovalProcess`

**File:** `app/Models/ApprovalProcess.php`

```php
// Kolom penting dan maknanya:
'id_karyawan_target'  // Approver dari RULE (siapa yang harus approve)
'id_karyawan_action'  // Approver yang BENAR-BENAR mengeksekusi
'status'              // Waiting | Pending | Approved | Rejected | Skipped | Revision
'uuid'                // UUID unik — dibuat saat approved, untuk verifikasi eksternal
```

---

### 3.3 `Karyawan`

**File:** `app/Models/Karyawan.php`

**Penting:** `primary_bank` **tidak** ada di `$appends` untuk menghindari N+1 query pada listing. Harus dipanggil manual:

```php
// Saat butuh di koleksi:
$karyawans->each(fn($k) => $k->append('primary_bank'));

// Saat butuh di model tunggal:
$karyawan->primary_bank; // langsung akses accessor
```

---

### 3.4 `Pinjaman`

**File:** `app/Models/Pinjaman.php`

```
Pinjaman
├── belongsTo Karyawan         → peminjam
├── hasMany AngsuranPinjaman   → jadwal angsuran
└── hasMany ApprovalProcess    → workflow approval
```

**Status Pinjaman:** Menggunakan `PengajuanStatus` enum (DRAFT → PENDING_APPROVAL → APPROVED | REJECTED).

---

## 4. Database Conventions

| Konvensi | Contoh |
|---|---|
| Nama tabel | `tbl_nama_tabel` (prefix `tbl_`) |
| Primary key | `id` (auto-increment) |
| Foreign key | `id_nama_relasi` (cth: `id_departemen`) |
| Timestamps | `created_at`, `updated_at` (Laravel default) |
| Soft delete | Tidak digunakan (hard delete) |
| Polimorfik | `owner_type`, `owner_id` (untuk `RekeningBank`) |

---

## 5. Key-Value Settings (`settings` table)

| Key | Deskripsi | Digunakan oleh |
|---|---|---|
| `account_receivable_employee` | ID AkunGL untuk Piutang Karyawan | `PinjamanService::createPaymentRequest()` |
| `default_program_loans` | ID ProgramKerja default untuk pinjaman | `PinjamanService` (fallback) |

Dikelola via `FinanceSettingController` → halaman `/admin/finance-settings`.

---

## 6. Migrasi Notable

| File Migrasi | Perubahan Signifikan |
|---|---|
| `2025_11_21_080238_create_approval_tables.php` | Tabel `tbl_approval_process` dan `tbl_approval_rules` dibuat pertama kali |
| `2025_11_28_195256_add_id_cuti_to_approval_process_table.php` | Menambah `id_cuti` agar Cuti bisa di-approve via sistem yang sama |
| `2026_04_08_050722_add_skipped_to_approval_process_status.php` | Menambah status `Skipped` dan `Revision` ke enum status; mengaktifkan Auto-Skip |
| `2026_04_08_065901_add_id_pinjaman_to_tbl_approval_process.php` | Menambah `id_pinjaman` agar Pinjaman masuk ke central approval |
| `2026_02_11_032724_add_uuid_to_approval_tables.php` | Menambah kolom `uuid` ke `tbl_approval_process` untuk verifikasi digital |
