# 05 — Routes & Otorisasi

## 1. Struktur Route

Semua route diawali dengan prefix `/admin` dan nama `admin.*`.

```
/
├── /                          → WelcomeController (halaman publik)
├── /verify/{uuid}             → PublicVerificationController (publik)
├── /dashboard                 → Redirect ke /admin/dashboard
├── /profile                   → ProfileController (auth required)
└── /admin
    ├── /login                 → AdminAuthenticatedSessionController
    ├── /auth/verify-pin       → PinVerificationController
    ├── /pin/*                 → PinController
    ├── /dashboard             → DashboardController
    └── [per-modul]
        ├── system.php         → /admin/users, /admin/roles, /admin/settings ...
        ├── hr.php             → /admin/karyawan, /admin/payrolls ...
        ├── finance.php        → /admin/budget, /admin/invoices ...
        └── ess.php            → /admin/pengajuan, /admin/cuti ...
```

---

## 2. Route Groups & Middleware

| File | Middleware | Role |
|---|---|---|
| `routes/admin/system.php` | `role:Super Admin` | Super Admin saja |
| `routes/admin/hr.php` | `role:Super Admin\|HR Manager\|HR Staff` | HR team |
| `routes/admin/finance.php` | `role:Super Admin\|Finance\|Staf Finance\|Finance Manager\|Finance Staff` | Finance team |
| `routes/admin/ess.php` | `role:Super Admin\|Manajer Departemen\|...` (semua role) | Semua karyawan |

> **Catatan:** Middleware `role:` berasal dari package **Spatie Laravel Permission**. Nilai role harus persis sama dengan nama role di database (case-sensitive).

---

## 3. Sistem Role

Menggunakan `spatie/laravel-permission`. Role didefinisikan dalam enum `App\Enums\Role`.

### 3.1 Daftar Role

| Enum Case | Nilai (String) | Status | Akses |
|---|---|---|---|
| `SUPER_ADMIN` | `Super Admin` | ✅ Aktif | Semua modul |
| `DIREKTUR` | `Direktur` | ✅ Aktif | ESS + view semua |
| `MANAJER_DEPARTEMEN` | `Manajer Departemen` | ✅ Aktif | ESS + approval |
| `STAF_FINANCE` | `Staf Finance` | ✅ Aktif | Finance + ESS |
| `FINANCE` | `Finance` | ✅ Aktif | Finance + ESS |
| `STAF` | `Staf` | ✅ Aktif | ESS |
| `HR_STAFF` | `HR Staff` | ✅ Aktif | HR + ESS |
| `HR_MANAGER` | `HR Manager` | ✅ Aktif | HR + ESS |
| `FINANCE_MANAGER` | `Finance Manager` | ✅ Aktif | Finance + ESS |
| `IT_SUPPORT` | `IT Support` | ✅ Aktif | ESS (termasuk kelola tiket) |
| `KARYAWAN` | `Karyawan` | ✅ Aktif | **Mobile App ESS only** (absensi, cuti, profil) |

### 3.2 Role Groups (Helper Methods)

```php
// Di controller/middleware, gunakan:
use App\Enums\Role;

Role::financeRoles()  // ['Super Admin', 'Finance', 'Finance Manager', 'Staf Finance']
Role::hrRoles()       // ['Super Admin', 'HR Manager', 'HR Staff']
Role::itRoles()       // ['Super Admin', 'IT Support'] (Kelola tiket via in-controller authorization)
Role::adminRoles()    // ['Super Admin', 'Direktur']
```

**Contoh penggunaan di controller:**
```php
// PengajuanController::index()
if (!Auth::user()->hasRole(Role::financeRoles())) {
    return redirect()->route('admin.pengajuan.my-requests');
}

// PengajuanController::resolveShowPermissions()
$canPay = $user->hasRole(Role::financeRoles())
    && $pengajuan->status_global === PengajuanStatus::APPROVED
    && $pengajuan->sisa_tagihan > 0;
```

---

## 4. Sistem PIN Verifikasi

Untuk aksi **approval** yang kritis, sistem memerlukan PIN verifikasi tambahan:

```
┌─────────────────────────────────────────────────────┐
│ 1. User klik "Setujui" / "Tolak" / "Revisi"        │
│ 2. Sistem cek session 'approval_pin_verified_at'    │
│    ├── ADA & < 5 menit lalu → Lanjut              │
│    └── TIDAK ADA / sudah expire:                    │
│        → Redirect ke halaman verifikasi PIN         │
│        → User input PIN                             │
│        → PIN dicek via PinVerificationController   │
│        → Session 'approval_pin_verified_at' = now() │
│        → Redirect kembali ke halaman sebelumnya    │
└─────────────────────────────────────────────────────┘
```

**Endpoint PIN:**

| Route | Method | Deskripsi |
|---|---|---|
| `admin.pin.status` | GET | Cek apakah PIN sudah diset |
| `admin.pin.set` | POST | Set PIN baru |
| `admin.pin.change` | POST | Ganti PIN |
| `admin.pin.verify` | POST | Verifikasi PIN |
| `admin.auth.verify-pin` | POST | Verifikasi PIN untuk approval |

---

## 5. Middleware Kustom

| Middleware | Deskripsi |
|---|---|
| `check.period:field` | Memvalidasi bahwa tanggal transaksi jatuh dalam periode akuntansi yang terbuka. Digunakan di journal dan invoice payment. |

**Contoh penggunaan:**
```php
Route::resource('journals', JournalController::class)
    ->middleware('check.period:tgl_jurnal');

Route::post('invoices/{invoice}/payment', ...)
    ->middleware('check.period:tgl_bayar');
```

---

## 6. Daftar Named Routes Penting

### ESS — Pengajuan
| Name | Method | URI |
|---|---|---|
| `admin.pengajuan.index` | GET | `/admin/pengajuan` |
| `admin.pengajuan.create` | GET | `/admin/pengajuan/create` |
| `admin.pengajuan.store` | POST | `/admin/pengajuan` |
| `admin.pengajuan.show` | GET | `/admin/pengajuan/{pengajuan}` |
| `admin.pengajuan.edit` | GET | `/admin/pengajuan/{pengajuan}/edit` |
| `admin.pengajuan.update` | PATCH | `/admin/pengajuan/{pengajuan}` |
| `admin.pengajuan.destroy` | DELETE | `/admin/pengajuan/{pengajuan}` |
| `admin.pengajuan.cancel` | PATCH | `/admin/pengajuan/{pengajuan}/cancel` |
| `admin.pengajuan.approvals` | GET | `/admin/pengajuan/approvals` |
| `admin.pengajuan.action` | POST | `/admin/pengajuan/{pengajuan}/action` |
| `admin.pengajuan.pay` | POST | `/admin/pengajuan/{pengajuan}/pay` |
| `admin.pengajuan.my-requests` | GET | `/admin/pengajuan/my-requests` |
| `admin.pengajuan.print` | GET | `/admin/pengajuan/{pengajuan}/print` |
| `admin.pengajuan.payment-schedule` | GET | `/admin/pengajuan/payment-schedule` |

### ESS — Settlement
| Name | Method | URI |
|---|---|---|
| `admin.settlement.create` | GET | `/admin/pengajuan/{pengajuan}/settlement/create` |
| `admin.settlement.store` | POST | `/admin/pengajuan/{pengajuan}/settlement` |
| `admin.settlement.edit` | GET | `/admin/pengajuan/{pengajuan}/settlement/edit` |
| `admin.settlement.update` | POST | `/admin/pengajuan/{pengajuan}/settlement/update` |
| `admin.settlement.verify` | POST | `/admin/pengajuan/{pengajuan}/settlement/verify` |
| `admin.settlement.reject` | POST | `/admin/pengajuan/{pengajuan}/settlement/reject` |

### Finance — Invoice
| Name | Method | URI |
|---|---|---|
| `admin.invoices.index` | GET | `/admin/invoices` |
| `admin.invoices.store` | POST | `/admin/invoices` |
| `admin.invoices.payment.store` | POST | `/admin/invoices/{invoice}/payment` |
| `admin.invoices.cancel` | PUT | `/admin/invoices/{invoice}/cancel` |
| `admin.invoices.print` | GET | `/admin/invoices/{invoice}/print` |

### HR — Pinjaman
| Name | Method | URI |
|---|---|---|
| `admin.pinjaman.index` | GET | `/admin/pinjaman` |
| `admin.pinjaman.store` | POST | `/admin/pinjaman` |
| `admin.pinjaman.action` | POST | `/admin/pinjaman/{pinjaman}/action` |

---

## 7. Ziggy — Route di Frontend (JavaScript)

Semua named route tersedia di Vue.js via Ziggy:

```javascript
// Di komponen Vue:
import { route } from '@/ziggy';

const url = route('admin.pengajuan.show', { pengajuan: id });
```

Konfigurasi Ziggy ada di `vite.config.js` dan di-inject via `AppServiceProvider`.

---

## 8. REST API Routes (Mobile App)

> Lihat **[10-mobile-api.md](./10-mobile-api.md)** untuk spesifikasi lengkap request/response.

Semua endpoint API diawali dengan `/api/v1`. Autentikasi via **Bearer Token (Sanctum)**.

### HR — Karyawan (Tambahan Web Admin)

| Name | Method | URI | Deskripsi |
|---|---|---|---|
| `admin.karyawan.generate-user` | POST | `/admin/karyawan/{id}/generate-user` | Buat akun login untuk karyawan |

### Mobile API — Auth

| Endpoint | Method | Auth | Deskripsi |
|---|---|---|---|
| `/api/v1/login` | POST | Public | Login, dapatkan Bearer Token |
| `/api/v1/logout` | POST | Bearer | Hapus token |
| `/api/v1/user` | GET | Bearer | Detail profil karyawan |
| `/api/v1/update-password` | POST | Bearer | Ganti password |

### Mobile API — Dashboard (BFF)

| Endpoint | Method | Auth | Deskripsi |
|---|---|---|---|
| `/api/v1/dashboard/home` | GET | Bearer | **Agregasi data Home Screen** (user + absensi hari ini + saldo cuti tahunan + pending approvals) |

### Mobile API — Absensi

| Endpoint | Method | Auth | Deskripsi |
|---|---|---|---|
| `/api/v1/absensi/today` | GET | Bearer | Status absensi hari ini |
| `/api/v1/absensi/clock-in` | POST | Bearer | Absen masuk (GPS + foto) |
| `/api/v1/absensi/clock-out` | POST | Bearer | Absen pulang (GPS + foto) |
| `/api/v1/absensi/history` | GET | Bearer | Riwayat per bulan (paginate) |

### Mobile API — Cuti

| Endpoint | Method | Auth | Deskripsi |
|---|---|---|---|
| `/api/v1/cuti/jenis` | GET | Bearer | Daftar jenis cuti |
| `/api/v1/cuti/balances` | GET | Bearer | Saldo cuti per tipe |
| `/api/v1/cuti/requests` | GET | Bearer | Riwayat pengajuan cuti saya |
| `/api/v1/cuti/request` | POST | Bearer | Buat pengajuan cuti baru |
| `/api/v1/cuti/approvals` | GET | Bearer | List cuti pending (khusus approver) |
| `/api/v1/cuti/approve/{id}` | POST | Bearer | Approve/reject cuti (khusus approver) |
