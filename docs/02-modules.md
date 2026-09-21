# 02 — Modul Sistem

PJH-ERP dibagi menjadi **4 domain route** utama, masing-masing memiliki kendali akses berbeda.

---

## 1. System Administration (`routes/admin/system.php`)

**Role yang diizinkan:** `Super Admin`

### 1.1 Manajemen User

| Controller | Deskripsi |
|---|---|
| `UserController` | CRUD user sistem (login credentials, assignment role) |
| `RoleController` | CRUD role (nama role, deskripsi) |
| `PermissionController` | CRUD permission dan penugasan ke role |

### 1.2 Pengaturan Sistem

| Controller | Deskripsi |
|---|---|
| `SettingsController` | Pengaturan umum sistem (nama perusahaan, dll) |
| `FinanceSettingController` | Konfigurasi akuntansi: akun piutang karyawan, dll |
| `ActivityLogController` | Audit trail semua perubahan di sistem |

---

## 2. HR & Payroll (`routes/admin/hr.php`)

**Role yang diizinkan:** `Super Admin`, `HR Manager`, `HR Staff`

### 2.1 Karyawan

| Controller | Method Utama | Deskripsi |
|---|---|---|
| `KaryawanController` | `index, create, store, edit, update, destroy` | Manajemen data karyawan |

**Data karyawan meliputi:**
- Identitas: NIK, nama lengkap, jabatan, jenis kelamin
- Keuangan: gaji pokok, status PTKP
- Kepegawaian: tanggal bergabung, status karyawan (Tetap/Kontrak/Magang)
- Rekening bank (polimorfik, via `tbl_rekening_bank`)

### 2.2 Cuti

| Controller | Method | Deskripsi |
|---|---|---|
| `JenisCutiController` | CRUD | Jenis cuti (Tahunan, Sakit, Melahirkan, dll) |

**Konfigurasi Jenis Cuti:**
- Jumlah hari per periode, apakah bisa mundur (carry over), batasan gender

### 2.3 Payroll

| Controller | Method | Deskripsi |
|---|---|---|
| `PayrollController` | CRUD + `approve` + `storeDetail` + `updateDetail` | Penggajian bulanan |

**Alur Payroll:**
1. Buat **PayrollHeader** (periode, departemen)
2. Tambah **PayrollDetail** per karyawan (gaji pokok + tunjangan + lembur − potongan)
3. Approve oleh HR Manager
4. Posting ke GL otomatis via `PayrollService`

### 2.4 Pinjaman Karyawan

| Controller | Method | Deskripsi |
|---|---|---|
| `PinjamanController` | `index, create, store, show` + `action` | Pengajuan pinjaman karyawan |

**Alur Pinjaman:**
1. Karyawan mengajukan pinjaman (jumlah + tenor)
2. `PinjamanService` generate angsuran bulanan otomatis
3. Approval multi-level via `ApprovalService`
4. Setelah approved → `PinjamanService::createPaymentRequest()` otomatis membuat `PengajuanHeader` untuk pencairan

### 2.5 Company Events

| Controller | Method | Deskripsi |
|---|---|---|
| `CompanyEventController` | CRUD | Event perusahaan yang tampil di kalender |

---

## 3. Finance & Accounting (`routes/admin/finance.php`)

**Role yang diizinkan:** `Super Admin`, `Finance`, `Staf Finance`, `Finance Manager`, `Finance Staff`

### 3.1 Master Data Anggaran

| Controller | Deskripsi |
|---|---|
| `PeriodeAnggaranController` | Periode anggaran (tahun/semester) |
| `ProgramKerjaController` | Program kerja per departemen |
| `PosAnggaranController` | Pos anggaran (akun → program mapping) |
| `BudgetController` | Master anggaran (komitmen, realisasi per pos) |
| `DepartemenController` | Master departemen |

### 3.2 General Ledger

| Controller | Deskripsi |
|---|---|
| `AkunGlController` | Chart of Accounts (CoA) |
| `KasBankController` | Master Kas/Bank (petty cash, rekening perusahaan) |
| `JournalController` | Jurnal manual (dilindungi `check.period` middleware) |
| `AccountingPeriodController` | Buka/tutup periode akuntansi |
| `AccountingReportController` | Laporan keuangan (Neraca, L/R, dll) |

### 3.3 Pajak

| Controller | Deskripsi |
|---|---|
| `TaxTypeController` | Master jenis pajak (PPN, PPh 23, dll) |
| `TaxReportController` | Laporan pajak + upload bukti potong |

### 3.4 Revenue (Penjualan)

| Controller | Method Penting | Deskripsi |
|---|---|---|
| `CustomerController` | CRUD | Master pelanggan |
| `InvoiceController` | CRUD + `storePayment` + `cancel` + `print` | Invoice penjualan |

**Alur Invoice:**
1. Buat Invoice (header + item + pajak)
2. `InvoiceService` otomatis posting GL + update budget realisasi
3. Catat pembayaran masuk (bisa cicilan)
4. Cetak (PDF via DomPDF)

### 3.5 Aset Tetap

| Controller | Method | Deskripsi |
|---|---|---|
| `AssetController` | CRUD + `runDepreciation` | Manajemen aset tetap |

**Fitur Aset:**
- Daftar aset dengan nilai buku
- Penyusutan otomatis per periode (run depreciation)
- Log penyusutan (`AsetDepresiasiLog`)

---

## 4. Employee Self-Service / ESS (`routes/admin/ess.php`)

**Role yang diizinkan:** Semua role (karyawan hingga direktur)

### 4.1 Kalender & Hari Libur

| Controller | Deskripsi |
|---|---|
| `CalendarController` | Tampilan kalender terintegrasi (cuti + libur + events) |
| `HariLiburController` | Manajemen hari libur nasional (bisa fetch otomatis dari API) |

### 4.2 Cuti (Employee View)

| Endpoint | Deskripsi |
|---|---|
| `cuti.my-requests` | Riwayat pengajuan cuti karyawan sendiri |
| `cuti.approvals` | Daftar cuti yang perlu disetujui (sebagai approver) |
| `cuti.management` | Manajemen saldo cuti semua karyawan |
| `cuti.generate` | Generate saldo cuti annual |

### 4.3 Pengajuan Dana / Payment Request

Ini adalah **modul inti** ESS. Terdapat 3 sub-controller:

#### PengajuanController — CRUD Inti
| Method | Endpoint | Deskripsi |
|---|---|---|
| `index` | `GET /pengajuan` | Daftar semua pengajuan (Finance/Admin) |
| `create` | `GET /pengajuan/create` | Form buat pengajuan baru |
| `store` | `POST /pengajuan` | Simpan pengajuan + init approval |
| `show` | `GET /pengajuan/{id}` | Detail pengajuan + timeline approval |
| `edit` | `GET /pengajuan/{id}/edit` | Form edit (hanya Draft/Pending/Revision) |
| `update` | `PATCH /pengajuan/{id}` | Update + restart approval jika Revision |
| `destroy` | `DELETE /pengajuan/{id}` | Hapus permanen (Draft/Pending saja) |
| `cancel` | `PATCH /pengajuan/{id}/cancel` | Void pengajuan, kembalikan budget |

**AJAX Endpoints (Helper):**

| Method | Endpoint | Deskripsi |
|---|---|---|
| `getProgramsByDepartemen` | `GET /pengajuan/get-programs` | Daftar program kerja per dept |
| `getAccountsByProgram` | `GET /pengajuan/get-accounts` | Akun GL ber-budget per program |
| `getTaxProgram` | `GET /pengajuan/get-tax-program` | Mapping akun pajak → program |
| `getBudgetBalance` | `GET /pengajuan/get-budget-balance` | Cek saldo anggaran real-time |
| `storeVendor` | `POST /pengajuan/store-vendor` | Buat vendor baru inline |
| `storeEmployeeBank` | `POST /pengajuan/store-employee-bank` | Tambah rekening karyawan inline |

#### ApprovalController — Alur Persetujuan
| Method | Endpoint | Deskripsi |
|---|---|---|
| `index` | `GET /pengajuan/approvals` | Daftar pengajuan pending persetujuan |
| `action` | `POST /pengajuan/{id}/action` | Approve / Reject / Revision |

#### PaymentController — Pembayaran
| Method | Endpoint | Deskripsi |
|---|---|---|
| `schedule` | `GET /pengajuan/payment-schedule` | Jadwal pembayaran |
| `printSchedule` | `POST /pengajuan/print-schedule` | Cetak jadwal |
| `store` | `POST /pengajuan/{id}/pay` | Catat pembayaran |

### 4.4 Settlement (Laporan Penggunaan)

Hanya untuk pengajuan bertipe `UangMuka`. Karyawan melaporkan penggunaan dana.

| Method | Endpoint | Deskripsi |
|---|---|---|
| `create` | `GET /pengajuan/{id}/settlement/create` | Form laporan |
| `store` | `POST /pengajuan/{id}/settlement` | Simpan laporan |
| `edit` | `GET /pengajuan/{id}/settlement/edit` | Edit laporan (saat REVISION) |
| `update` | `POST /pengajuan/{id}/settlement/update` | Update laporan |
| `verify` | `POST /pengajuan/{id}/settlement/verify` | Verifikasi Finance |
| `reject` | `POST /pengajuan/{id}/settlement/reject` | Tolak laporan |

### 4.5 Approval Rules

Konfigurasi siapa yang menyetujui per departemen dan tipe dokumen:

| Controller | Deskripsi |
|---|---|
| `ApprovalRuleController` | CRUD aturan approval (departemen + tipe + level + approver) |

### 4.6 IT Support Tickets

> **Catatan desain:** Tiket ada dalam modul ESS (bukan modul IT terpisah). Ini karena *semua karyawan* bisa membuat tiket (self-service), sementara pengelolaan oleh IT terjadi pada halaman yang sama dengan akses terkontrol secara programatik (bukan via route middleware).

**Dua tampilan dalam satu controller (`TicketController`):**

| Siapa | Endpoint | Akses | Deskripsi |
|---|---|---|---|
| Semua karyawan | `tickets.my-requests` | Tiket milik sendiri | Buat & pantau tiket saya |
| Semua karyawan | `tickets.create` | — | Form tiket baru |
| Semua karyawan | `tickets.show` | Tiket milik sendiri saja | Detail tiket + komentar |
| **IT Support** / Super Admin | `tickets.index` | Semua tiket | Management view (filter, sort by priority) |
| **IT Support** / Super Admin | `tickets.update` | — | Update status, prioritas, assignment |
| **IT Support** / Super Admin | `tickets.comments.store` | Semua tiket | Reply ke tiket siapapun |
| Super Admin only | `tickets.destroy` | — | Hapus tiket permanen |

**Otorisasi in-controller (bukan middleware):**
```php
// Hanya IT Support + Super Admin yang bisa akses management view
if (!$user->hasRole(Role::itRoles())) { abort(403); }
// Role::itRoles() = ['Super Admin', 'IT Support']
```

> ⚠️ **Tidak ada role "Helpdesk Manager"** di sistem saat ini. Enum `Role` hanya mendefinisikan `IT_SUPPORT = 'IT Support'` sebagai satu-satunya role IT yang aktif. Jika dibutuhkan pemisahan senior/junior IT, perlu ditambahkan role baru.

---

## 5. Ringkasan Modul

```
PJH-ERP
├── [SYSTEM]  Users, Roles, Permissions, Settings, Logs
├── [HR]      Karyawan, JenisCuti, Payroll, Pinjaman, CompanyEvents
├── [FINANCE] Dept, Vendors, Budget, GL, Kas/Bank, Tax, Invoice, Aset
└── [ESS]     Calendar, HariLibur, Cuti, Pengajuan, Settlement,
              ApprovalRules, Tickets
```
