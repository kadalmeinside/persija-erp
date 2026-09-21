# 07 — Enums & Konfigurasi

## 1. PHP Enums (`app/Enums/`)

### 1.1 `PengajuanStatus`

Status lifecycle pengajuan dana dan pinjaman.

| Case | Value | Label Indonesia | Warna | Deskripsi |
|---|---|---|---|---|
| `DRAFT` | `Draft` | Draft | abu-abu | Belum disubmit |
| `PENDING_APPROVAL` | `Pending Approval` | Menunggu Persetujuan | kuning | Sedang dalam proses approval |
| `APPROVED` | `Approved` | Disetujui | biru | Semua level approve |
| `REJECTED` | `Rejected` | Ditolak | merah | Ditolak oleh approver |
| `REVISION` | `Revision` | Perlu Revisi | oranye | Diminta approver untuk diperbaiki |
| `VERIFICATION` | `Verification` | Verifikasi Finance | ungu | Laporan settlement sudah masuk, menunggu verifikasi |
| `PAID` | `Paid` | Sudah Dibayar | hijau | Sudah cair |
| `SETTLED` | `Settled` | Selesai | emerald | Laporan diterima Finance |
| `CANCELLED` | `Cancelled` | Dibatalkan | merah | Dibatalkan oleh pengaju/admin |

**Cara penggunaan:**
```php
// Di Model (cast otomatis)
protected $casts = [
    'status_global' => PengajuanStatus::class,
];

// Di controller
$pengajuan->status_global === PengajuanStatus::APPROVED

// Di blade/view
$pengajuan->status_global->label()  // → "Disetujui"
$pengajuan->status_global->color()  // → "blue"
```

---

### 1.2 `Role`

Seluruh role yang digunakan di sistem.

```php
enum Role: string {
    case SUPER_ADMIN        = 'Super Admin';
    case MANAJER_DEPARTEMEN = 'Manajer Departemen';
    case STAF_FINANCE       = 'Staf Finance';
    case STAF               = 'Staf';
    case DIREKTUR           = 'Direktur';
    case FINANCE            = 'Finance';
    case HR_STAFF           = 'HR Staff';
    case IT_SUPPORT         = 'IT Support';
    case FINANCE_MANAGER    = 'Finance Manager'; // Planned
    case HR_MANAGER         = 'HR Manager';      // Planned
}
```

**Helper Groups:**

| Method | Return |
|---|---|
| `Role::financeRoles()` | `['Super Admin', 'Finance', 'Finance Manager', 'Staf Finance']` |
| `Role::hrRoles()` | `['Super Admin', 'HR Manager', 'HR Staff']` |
| `Role::itRoles()` | `['Super Admin', 'IT Support']` |
| `Role::adminRoles()` | `['Super Admin', 'Direktur']` |

> **Aturan:** Jangan pernah hardcode string role di code. Selalu gunakan enum ini.

---

### 1.3 `PaymentMethod`

| Case | Value | Deskripsi |
|---|---|---|
| `CASH` | `Cash` | Pembayaran tunai |
| `TRANSFER` | `Transfer` | Transfer bank |

---

### 1.4 `InvoiceStatus`

| Case | Deskripsi |
|---|---|
| `Unpaid` | Belum dibayar |
| `Partial` | Bayar sebagian |
| `Paid` | Lunas |
| `Overdue` | Jatuh tempo |
| `Cancelled` | Dibatalkan |

---

### 1.5 `PayrollStatus`

| Case | Deskripsi |
|---|---|
| `Draft` | Dalam proses pembuatan |
| `Approved` | Telah disetujui dan dicairkan |

---

### 1.6 `AngsuranStatus`

Status cicilan pinjaman per bulan.

| Case | Value | Deskripsi |
|---|---|---|
| `PENDING` | `Pending` | Belum dibayar |
| `PAID` | `Paid` | Sudah dipotong dari gaji |
| `OVERDUE` | `Overdue` | Lewat jatuh tempo |

---

### 1.7 `PengajuanType` (Tipe Pengajuan)

| Value | Deskripsi |
|---|---|
| `Langsung` | Pembayaran langsung — tanpa settlement |
| `UangMuka` | Uang muka — harus ada laporan settlement |
| `Reimburse` | Penggantian biaya — bayar ke karyawan |

---

## 2. Konfigurasi Persija ERP

### 2.1 Environment Variables (`.env`)

```env
APP_NAME="Persija ERP"
APP_ENV=local
APP_KEY=             # Generate via php artisan key:generate

DB_CONNECTION=sqlite # Ganti ke mysql untuk production
DB_DATABASE=/path/to/database.sqlite

QUEUE_CONNECTION=database

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=ap1
```

### 2.2 Settings Table (Key-Value Store)

Dikonfigurasi via UI `/admin/finance-settings`:

| Key | Deskripsi | Berdampak Pada |
|---|---|---|
| `account_receivable_employee` | ID AkunGL untuk "Piutang Karyawan" | Otomatis buat PengajuanHeader saat Pinjaman approved |
| `default_program_loans` | ID ProgramKerja default pinjaman | Fallback program saat tidak ada program di dept approver |

> **Penting:** Jika `account_receivable_employee` belum diset, maka approval pinjaman akan melempar `Exception` dan transaksi dibatalkan. Pastikan setting ini dikonfigurasi sebelum ada pinjaman.

---

## 3. Accounting Period Guard

Middleware `check.period` memblokir transaksi yang jatuh di luar **periode akuntansi yang terbuka**:

- Periode bisa dibuka/ditutup via `AccountingPeriodController`
- Saat ditutup, jurnal manual dan pembayaran invoice **tidak bisa diposting**

---

## 4. Nomor Dokumen

Format nomor dokumen yang di-generate oleh `DocumentNumberService`:

| Dokumen | Format | Contoh |
|---|---|---|
| Pengajuan Dana | `PJ/YYYY/MM/NNNNN` | `PJ/2026/04/00001` |
| Pinjaman (Pencairan) | `PN/YYYY/MM/NNNNN` | `PN/2026/04/00002` |
| Invoice | `INV/YYYY/MM/NNNNN` | `INV/2026/04/00010` |
| Vendor | `VND/NNNNN` | `VND/00042` |

Setiap generate nomor menggunakan **database lock** untuk memastikan tidak ada nomor duplikat dalam kondisi concurrent.

---

## 5. Konfigurasi Akuntansi (`config/accounting.php`)

```php
// Akun Piutang Usaha untuk posting AR
'accounts' => [
    'ar' => env('ACCOUNTING_AR_ACCOUNT_ID', null),
],
```

Jika tidak dikonfigurasi, `InvoiceService` akan mencari akun dengan nama mengandung "Piutang Usaha" atau tipe "Piutang" di CoA.
