# 01 — Arsitektur Sistem

## 1. Stack Teknologi

| Layer | Teknologi |
|---|---|
| **Backend Framework** | Laravel 12.x (PHP ≥ 8.2) |
| **Frontend Web (Admin)** | Inertia.js v2 + Vue.js (SSR-ready) |
| **Mobile App** | Flutter (Native Android/iOS) |
| **Mobile Auth** | Laravel Sanctum (Token-based) |
| **Database** | MySQL (development & production) |
| **Antrian Pekerjaan** | Laravel Queue (`database` driver) |
| **Notifikasi Real-time** | Pusher (via `pusher/pusher-php-server`) |
| **Otorisasi Role** | Spatie Laravel Permission v6 |
| **Audit Log** | Spatie Laravel Activitylog v4 |
| **Ekspor PDF** | barryvdh/laravel-dompdf v3 |
| **Ekspor Excel** | Maatwebsite Excel v3 |
| **QR Code** | SimpleSoftwareIO/simple-qrcode |
| **Route (JS)** | Tightenco Ziggy |

---

## 2. Struktur Direktori

```
persija-erp/
├── app/
│   ├── Console/           # Artisan komando (scheduled jobs)
│   ├── Enums/             # PHP Enum (PengajuanStatus, Role, dll)
│   ├── Events/            # Laravel Events
│   ├── Exports/           # Export Excel (Maatwebsite)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/     # Controller utama tiap modul
│   │   │   │   ├── Pengajuan/  # Sub-controller Pengajuan
│   │   │   │   └── Auth/       # Autentikasi admin
│   │   │   ├── Auth/      # Breeze auth controllers
│   │   │   └── Public/    # Controller publik (verifikasi)
│   │   ├── Middleware/    # Custom middleware
│   │   └── Requests/      # Form Request (validasi terpusat)
│   ├── Imports/           # Import Excel
│   ├── Jobs/              # Queued Jobs
│   ├── Models/            # Eloquent Models (59 model)
│   ├── Notifications/     # Notifikasi (email, database)
│   ├── Providers/         # Service Providers
│   └── Services/          # Business Logic Layer (10 service)
├── database/
│   ├── factories/         # Model Factories (testing)
│   ├── migrations/        # 58 file migrasi
│   └── seeders/           # Database Seeder
├── resources/
│   ├── js/                # Vue.js/Inertia frontend
│   └── views/             # Blade templates (minimal)
├── routes/
│   ├── web.php            # Entry point routing web admin
│   ├── api.php            # REST API untuk Mobile App (Sanctum)
│   └── admin/             # Routes terpisah per domain
│       ├── system.php     # Super Admin only
│       ├── hr.php         # HR & Payroll
│       ├── finance.php    # Finance & Accounting
│       └── ess.php        # Employee Self-Service
└── tests/
    ├── Feature/           # Feature/Integration Tests
    └── Unit/              # Unit Tests
```

---

## 3. Pola Arsitektur

### 3.1 MVC + Service Layer

Sistem menggunakan **MVC** yang diperkuat dengan **Service Layer** untuk memisahkan business logic dari controller:

```
HTTP Request
    ↓
Route (web.php / admin/*.php)
    ↓
Controller (Http/Controllers)
    ↓ memanggil
Service Layer (app/Services)
    ↓ berinteraksi dengan
Model (app/Models) + Database
    ↓
Response via Inertia.js (JSON → Vue)
```

### 3.2 Form Request Validation

Semua validasi input tersentralisasi di `app/Http/Requests/`:
- `Pengajuan/StorePengajuanRequest` — validasi pembuatan pengajuan baru
- `Pengajuan/UpdatePengajuanRequest` — validasi pembaruan pengajuan

### 3.3 Inertia.js Full-Stack (Web Admin)

Backend me-render halaman dengan `Inertia::render('Admin/ComponentName', ['data' => ...])`.
Frontend Vue menerima data tersebut sebagai `props`.
Data web admin mengalir melalui Inertia SSR.

### 3.4 REST API (Mobile App — BFF Pattern)

Seluruh endpoint untuk Mobile App berada di `routes/api.php` dengan prefix `/api/v1`.
Autentikasi menggunakan **Laravel Sanctum** (token-based, bukan session).
Endpoint dashboard menggunakan **BFF (Backend for Frontend) Pattern**: satu endpoint agregasi (`GET /api/v1/dashboard/home`) menggantikan banyak panggilan terpisah, mempercepat load awal aplikasi mobile.

### 3.4 Queued Jobs & Events

Notifikasi dikirim secara **asynchronous** melalui Laravel Queue:
- Queue driver: `database` (tabel `jobs` dikelola oleh migrasi)
- Notifikasi tersimpan di tabel `notifications`

---

## 4. Diagram Arsitektur High-Level

```
┌──────────────────┐       ┌────────────────────────┐
│   Browser        │       │   Mobile App (Flutter) │
│   (Vue.js)       │       │   Android / iOS        │
│   Inertia.js     │       │   JWT via Sanctum      │
└────────┬─────────┘       └───────────┬────────────┘
         │ Inertia XHR                 │ REST API
         │ (Cookie/Session)            │ (Bearer Token)
┌────────▼─────────────────────────────▼────────────┐
│                   Laravel Backend                  │
│                                                    │
│  routes/web.php ──→ Admin Controllers (Inertia)    │
│  routes/api.php ──→ Api Controllers (JSON)         │
│                           │                        │
│              ┌────────────▼────────────────┐       │
│              │       Services Layer        │       │
│              │     (app/Services/)         │       │
│              └────────────┬────────────────┘       │
│                           │                        │
│  ┌────────────────┐  ┌────▼─────────────────────┐  │
│  │  Queue / Jobs  │  │    Eloquent Models       │  │
│  │ (Notifikasi)   │  │    (app/Models/)         │  │
│  └────────────────┘  └────┬─────────────────────┘  │
└───────────────────────────│────────────────────────┘
                            │
          ┌─────────────────▼──────────────┐
          │       MySQL (dev & prod)       │
          │  SQLite :memory: (tests only)  │
          └────────────────────────────────┘
```

> **Catatan SQLite:** SQLite **hanya digunakan saat menjalankan test** (`php artisan test`).
> Ini dikonfigurasi di `phpunit.xml` (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`).
> Development dan production keduanya menggunakan **MySQL** sesuai `.env`.

---

## 5. Dependency Injection

Service classes di-*inject* melalui **constructor injection** Laravel:

```php
// Contoh: PengajuanController
public function __construct(
    PengajuanService $pengajuanService,
    BudgetCheckService $budgetCheckService,
    ApprovalService $approvalService
) { ... }
```

Semua service binding dikelola otomatis oleh Laravel Service Container (auto-resolve).
