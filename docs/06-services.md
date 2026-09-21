# 06 — Services Layer

Semua business logic yang kompleks dipisahkan ke dalam **Service Classes** di `app/Services/`.
Controller hanya bertugas menerima request, memanggil service, dan mengembalikan response.

---

## 1. ApprovalService

**File:** `app/Services/ApprovalService.php` (~410 baris)

**Tanggung jawab:** Mengelola seluruh workflow persetujuan multi-level.

| Method | Signature | Deskripsi |
|---|---|---|
| `initApproval` | `($model)` | Inisialisasi langkah approval berdasarkan rules |
| `approve` | `($model, $karyawanIdAction, $catatan = null)` | Setujui langkah saat ini |
| `reject` | `($model, $karyawanIdAction, $catatan)` | Tolak langkah saat ini |
| `revision` | `($model, $karyawanIdAction, $catatan)` | Minta revisi ke pengaju |

> Lihat **[03-approval-workflow.md](./03-approval-workflow.md)** untuk dokumentasi lengkap.

---

## 2. PengajuanService

**File:** `app/Services/PengajuanService.php` (~330 baris)

**Tanggung jawab:** CRUD Pengajuan Dana termasuk budget check, tax calculation, dan file attachment.

**Dependencies:**
```php
BudgetCheckService $budgetCheckService
ApprovalService $approvalService
CalculationService $calculationService
```

| Method | Deskripsi |
|---|---|
| `create(array $data, $file)` | Buat pengajuan baru: upload file, resolve bank info, simpan header + detail, check & commit budget, init approval |
| `update(PengajuanHeader, array $data, $file)` | Update pengajuan: reversal budget lama, update header + detail |
| `destroyPengajuan(PengajuanHeader)` | Hapus permanen: uncommit budget, hapus file, hapus data terkait |
| `cancelPengajuan(PengajuanHeader, $reason, $userId)` | Void pengajuan: uncommit budget, tandai Cancelled, auto-reject step pending |

**Alur `create()`:**
```
1. Upload file attachment ke storage/public/attachments
2. Resolve info bank penerima (dari Vendor atau Karyawan pilihan)
3. Generate nomor pengajuan via DocumentNumberService
4. Simpan PengajuanHeader
5. Loop items:
   a. BudgetCheckService::check() → validasi saldo anggaran
   b. CalculationService::calculateTax() → hitung pajak
   c. Simpan PengajuanDetail
   d. BudgetCheckService::commitBudget() → update saldo anggaran
6. ApprovalService::initApproval() → mulai workflow
```

---

## 3. BudgetCheckService

**File:** `app/Services/BudgetCheckService.php` (~200 baris)

**Tanggung jawab:** Validasi dan manajemen saldo anggaran.

| Method | Deskripsi |
|---|---|
| `check($deptId, $akunId, $programId, $nominal, $date)` | Cek apakah ada saldo cukup; return `['success', 'warning', 'budget_id']` |
| `getSisaSaldoDB($deptId, $akunId, $programId, $date)` | Ambil sisa saldo real-time dari DB |
| `commitBudget($budgetId, $nominal)` | Tambahkan nominal ke `anggaran_terpakai` |
| `unCommitBudget($budgetId, $nominal)` | Kurangi `anggaran_terpakai` (reversal) |
| `getBudgetId($deptId, $akunId, $programId, $date)` | Cari ID BudgetMaster untuk kombinasi ini |

**Logika `check()`:**
- Cari `BudgetMaster` yang sesuai (dept + akun + program + periode aktif)
- Jika **tidak ada budget** → `success=false` (pengajuan ditolak)
- Jika **saldo cukup** → `success=true`
- Jika **saldo kurang tapi akun liability** (Utang/Kewajiban) → izinkan dengan warning
- Return `budget_id` untuk di-commit setelah pengajuan berhasil

---

## 4. CalculationService

**File:** `app/Services/CalculationService.php` (~80 baris)

**Tanggung jawab:** Kalkulasi pajak yang terstandarisasi.

| Method | Deskripsi |
|---|---|
| `calculateTax($nominal, $rate, $tipe)` | Hitung nominal pajak; return `['tax_rate', 'tax_amount']` |

**Logika tipe pajak:**
- `'inclusive'` → pajak sudah termasuk dalam nominal: `tax = nominal - (nominal / (1 + rate/100))`
- `'exclusive'` (default) → pajak ditambahkan: `tax = nominal * rate / 100`

---

## 5. DocumentNumberService

**File:** `app/Services/DocumentNumberService.php` (~130 baris)

**Tanggung jawab:** Generate nomor dokumen yang unik dan sequential (tanpa race condition).

| Method | Format | Contoh |
|---|---|---|
| `pengajuan()` | `PJ/YYYY/MM/XXXXX` | `PJ/2026/04/00001` |
| `pinjaman()` | `PN/YYYY/MM/XXXXX` | `PN/2026/04/00001` |
| `invoice()` | `INV/YYYY/MM/XXXXX` | `INV/2026/04/00001` |
| `vendor()` | `VND/XXXXX` | `VND/00042` |

**Implementasi atomic:**
Menggunakan `DB::transaction` + `lockForUpdate()` untuk mencegah nomor duplikat saat concurrent request.

---

## 6. InvoiceService

**File:** `app/Services/InvoiceService.php` (~400 baris)

**Tanggung jawab:** Seluruh siklus hidup invoice penjualan.

| Method | Deskripsi |
|---|---|
| `createInvoice(array $data)` | Buat invoice: kalkulasi, GL posting, update budget realisasi |
| `updateInvoice(InvoiceHeader, array $data)` | Update: reversal GL + budget lama, buat ulang |
| `cancelInvoice(InvoiceHeader)` | Void: reversal GL (jurnal kebalikan), update budget |
| `destroyInvoice(InvoiceHeader)` | Hapus permanen: reversal budget, hapus jurnal + detail |

**Alur `createInvoice()`:**
```
1. Kalkulasi subtotal dari items
2. Kalkulasi PPN & PPh (batch load TaxType — 1 query)
3. Generate nomor invoice via DocumentNumberService
4. Simpan InvoiceHeader
5. Buat InvoiceDetail per item + siapkan GL entries
6. Buat GL entries pajak (PPN Keluaran, Prepaid PPh)
7. Buat GL entry Piutang Usaha (AR Debit)
8. Post semua ke Jurnal via GLService::createJournal()
9. Update budget realisasi revenue via RevenueBudgetService
```

---

## 7. PinjamanService

**File:** `app/Services/PinjamanService.php` (~193 baris)

**Tanggung jawab:** Logika pinjaman karyawan.

| Method | Deskripsi |
|---|---|
| `createPinjaman(array $data)` | Buat Pinjaman + generate jadwal angsuran + init approval |
| `createPaymentRequest(Pinjaman, ?Karyawan)` | Otomatis buat PengajuanHeader untuk pencairan dana setelah approval |
| `getAuditLogs(Pinjaman)` | Ambil audit log pinjaman + angsurannya |

**Catatan desain `createPaymentRequest()`:**
- `$approverKaryawan` di-pass dari `ApprovalService` (bukan dari `Auth::user()`)
- Ini sengaja untuk **decoupling** service dari konteks HTTP
- Jika `null` (auto-approve case), fallback ke departemen karyawan peminjam

---

## 8. GLService

**File:** `app/Services/GLService.php` (~90 baris)

**Tanggung jawab:** Helper untuk posting ke General Ledger.

| Method | Deskripsi |
|---|---|
| `createJournal($date, $desc, $details, $module, $refId, $type)` | Buat JurnalHeader + JurnalDetail; validasi debit == kredit |

**Parameter `$details`** adalah array of:
```php
[
    'id_akun'    => int,
    'debit'      => float,
    'kredit'     => float,
    'keterangan' => string,
]
```

---

## 9. PayrollService

**File:** `app/Services/PayrollService.php` (~400 baris)

**Tanggung jawab:** Proses penggajian karyawan.

| Method | Deskripsi |
|---|---|
| `process(Payroll)` | Hitung dan posting gaji semua karyawan dalam payroll |
| `approve(Payroll, $userId)` | Finalisasi payroll → posting GL |
| `calculateGross($karyawan, $komponen)` | Hitung gaji bruto + tunjangan |
| `calculateDeductions($karyawan)` | Hitung potongan (BPJS, angsuran pinjaman, dll) |

---

## 10. RevenueBudgetService

**File:** `app/Services/RevenueBudgetService.php` (~100 baris)

**Tanggung jawab:** Update realisasi anggaran pendapatan saat invoice dibuat/diubah/dihapus.

| Method | Deskripsi |
|---|---|
| `updateRealization(InvoiceHeader)` | Tambah realisasi ke BudgetMaster |
| `reverseRealization(InvoiceHeader)` | Kurangi realisasi dari BudgetMaster |

---

## Diagram Dependensi Services

```
PengajuanController
  └── PengajuanService
        ├── BudgetCheckService
        ├── ApprovalService
        │     └── PinjamanService (dipanggil saat Pinjaman final approved)
        └── CalculationService

InvoiceController
  └── InvoiceService
        ├── GLService
        └── RevenueBudgetService

PayrollController
  └── PayrollService
        └── GLService
```
