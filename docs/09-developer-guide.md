# 09 — Panduan Pengembang

## 1. Setup Lokal

### Prasyarat

| Software | Versi Minimal |
|---|---|
| PHP | 8.2 |
| Composer | 2.x |
| Node.js | 18+ |
| npm | 9+ |
| SQLite | 3.x (dev) |

### Langkah Setup

```bash
# 1. Clone repository
git clone <repo-url> persija-erp
cd persija-erp

# 2. Install PHP dependencies
composer install

# 3. Install Node.js dependencies
npm install

# 4. Salin environment config
cp .env.example .env

# 5. Generate application key
php artisan key:generate

# 6. Buat file database SQLite
touch database/database.sqlite

# 7. Jalankan migrasi + seeder
php artisan migrate --seed

# 8. Jalankan semua server (backend + queue + vite)
composer run dev
```

> `composer run dev` menjalankan 4 proses sekaligus:
> - `php artisan serve` — Web server
> - `php artisan queue:listen` — Queue worker
> - `php artisan pail` — Log viewer
> - `npm run dev` — Vite dev server

---

## 2. Menjalankan Test

```bash
# Semua test
php artisan test

# Test cepat (parallel, butuh setup)
php artisan test --parallel

# Filter test tertentu
php artisan test --filter="ApprovalServiceTest"
php artisan test --filter="identity_guard"

# Dengan verbose output
php artisan test --verbose
```

---

## 3. Konvensi Kode

### 3.1 Nama File & Kelas

| Tipe | Konvensi | Contoh |
|---|---|---|
| Controller | `{Nama}Controller.php` | `PengajuanController.php` |
| Service | `{Nama}Service.php` | `ApprovalService.php` |
| Model | `{NamaModel}.php` (PascalCase) | `PengajuanHeader.php` |
| Request | `{Action}{Model}Request.php` | `StorePengajuanRequest.php` |
| Enum | `{Nama}.php` | `PengajuanStatus.php` |
| Test | `{Nama}Test.php` | `ApprovalServiceTest.php` |

### 3.2 Konvensi Nama DB

- **Tabel:** `tbl_nama_tabel` (always prefix `tbl_`)
- **Primary Key:** `id`
- **Foreign Key:** `id_nama_relasi` (cth: `id_departemen`, `id_pengaju`)
- **Timestamps:** `created_at`, `updated_at` (Laravel default)
- **Status fields:** gunakan nilai dari PHP Enum, bukan angka

### 3.3 Aturan Business Logic

> **Penting:** Business logic **wajib** ada di Service Layer, bukan di Controller.

```php
// ❌ JANGAN: logic di controller
class PengajuanController {
    public function store(Request $request) {
        $pengajuan = PengajuanHeader::create([...]);
        // langsung manipulasi budget, approval dll di sini
    }
}

// ✅ BENAR: controller hanya delegate ke service
class PengajuanController {
    public function store(StorePengajuanRequest $request) {
        $result = $this->pengajuanService->create($request->validated(), $request->file('attachment'));
        return redirect()->route('admin.pengajuan.index')->with('success', 'Berhasil!');
    }
}
```

### 3.4 Aturan Role String

```php
// ❌ JANGAN: hardcode string
if ($user->hasRole('Finance')) { ... }
if ($user->hasRole('Super Admin|Finance')) { ... }

// ✅ BENAR: gunakan Enum
use App\Enums\Role;

if ($user->hasRole(Role::FINANCE->value)) { ... }
if ($user->hasRole(Role::financeRoles())) { ... }
```

### 3.5 Eager Loading (N+1 Prevention)

```php
// ❌ JANGAN: menyebabkan N+1
$pengajuans = PengajuanHeader::all();
foreach ($pengajuans as $p) {
    echo $p->pengaju->nama_lengkap; // N+1 query!
}

// ✅ BENAR: eager load relasi yang dibutuhkan
$pengajuans = PengajuanHeader::with(['pengaju', 'departemen', 'currentStep'])->get();
```

---

## 4. Cara Menambah Modul Baru

### Langkah-langkah

```bash
# 1. Buat Model + Migrasi
php artisan make:model NamaModel -m

# 2. Tambahkan kolom di migrasi
# (edit file di database/migrations/)

# 3. Buat Controller
php artisan make:controller Admin/NamaController --resource

# 4. Buat Service class (jika ada business logic kompleks)
# (buat manual di app/Services/NamaService.php)

# 5. Buat Form Request
php artisan make:request Nama/StoreNamaRequest

# 6. Tambahkan route di file yang sesuai:
#    - routes/admin/system.php  (Super Admin saja)
#    - routes/admin/hr.php      (HR)
#    - routes/admin/finance.php (Finance)
#    - routes/admin/ess.php     (Semua)

# 7. Buat test
php artisan make:test Feature/NamaFlowTest

# 8. Jalankan migrasi
php artisan migrate
```

### Checklist Modul Baru

- [ ] Model dengan `$fillable`, `$casts`, dan relasi lengkap
- [ ] Migrasi tabel dengan nama `tbl_nama_tabel`
- [ ] Controller menggunakan Form Request (bukan `$request->input()` langsung)
- [ ] Business logic ada di Service, bukan controller
- [ ] Route di file yang tepat dengan middleware role yang benar
- [ ] Test minimal: smoke test akses, create, update
- [ ] Jika ada status lifecycle → gunakan PHP Enum

---

## 5. Cara Menambah Approval Rule

Dilakukan via UI `/admin/approval-rules`. Tidak perlu modifikasi kode.

**Yang perlu diisi:**
- Departemen
- Tipe: `Pengajuan` | `Cuti` | `Pinjaman`
- Level Order: angka urutan (1 = pertama)
- Karyawan Approver: siapa yang harus approve
- Label (opsional): teks yang muncul di timeline

---

## 6. Cara Reset Approval Flow

Jika ada dokumen yang stuck di approval karena rule berubah:

```bash
# Via Artisan Tinker
php artisan tinker

# Reset dan mulai ulang approval flow pengajuan tertentu
$pengajuan = PengajuanHeader::find(ID);
$pengajuan->approvalProcess()->delete();
app(App\Services\ApprovalService::class)->initApproval($pengajuan->fresh());
```

---

## 7. Debug & Logging

### Laravel Log

```bash
# Lihat log real-time
php artisan pail

# Atau manual
tail -f storage/logs/laravel.log
```

### Activity Log (Audit Trail)

```bash
# Via Tinker — lihat perubahan model tertentu
php artisan tinker

use Spatie\Activitylog\Models\Activity;
Activity::where('subject_type', 'App\Models\PengajuanHeader')
        ->where('subject_id', 1)
        ->get();
```

### ApprovalService Debug

Service ini sangat verbose dalam logging. Cari di log dengan tag:
- `--- START INIT APPROVAL ---`
- `Type: PengajuanHeader, ID: X`
- `Auto-Skip Level X:`
- `Approval Selesai. Status: Approved.`

---

## 8. Deployment Notes

### Production Checklist

```bash
# 1. Set environment
APP_ENV=production
APP_DEBUG=false

# 2. Jalankan migrasi
php artisan migrate --force

# 3. Optimize
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Build frontend
npm run build

# 5. Jalankan queue worker (pakai supervisor/systemd)
php artisan queue:work --daemon --tries=3
```

### Queue Worker (Supervisor config)

```ini
[program:persija-erp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work database --tries=3 --timeout=90
autostart=true
autorestart=true
user=www-data
numprocs=2
```

---

## 9. Troubleshooting Umum

| Masalah | Penyebab | Solusi |
|---|---|---|
| Pinjaman approval error "Konfigurasi Akun Piutang belum diatur" | Setting `account_receivable_employee` kosong | Set via `/admin/finance-settings` |
| Approval tidak bisa di-execute (403) | User bukan `id_karyawan_target` dari step | Cek ApprovalRule — siapa yang ditunjuk sebagai approver |
| Budget error saat buat pengajuan | Tidak ada BudgetMaster aktif atau saldo habis | Cek BudgetMaster + PeriodeAnggaran aktif |
| Invoice tidak bisa dicatat pembayaran | Akuntansi Periode sudah ditutup | Buka kembali periode via `/admin/accounting-periods` |
| Notifikasi tidak terkirim | Queue worker tidak jalan | Jalankan `php artisan queue:listen` |
| `N+1 query` di halaman listing | Relasi tidak di-eager load | Tambahkan `with([...])` di query |
