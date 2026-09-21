# 01 — Arsitektur Sistem

## 1. Stack Teknologi

| Layer | Teknologi |
|---|---|
| **Backend Framework** | Laravel 12.x (PHP ≥ 8.2) |
| **Frontend** | Inertia.js v2 + Vue.js (SSR-ready) |
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
│   ├── web.php            # Entry point routing
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

### 3.3 Inertia.js Full-Stack

Backend me-render halaman dengan `Inertia::render('Admin/ComponentName', ['data' => ...])`.
Frontend Vue menerima data tersebut sebagai `props`.
Tidak ada REST API terpisah — data mengalir melalui Inertia SSR.

### 3.4 Queued Jobs & Events

Notifikasi dikirim secara **asynchronous** melalui Laravel Queue:
- Queue driver: `database` (tabel `jobs` dikelola oleh migrasi)
- Notifikasi tersimpan di tabel `notifications`

---

## 4. Diagram Arsitektur High-Level

```
┌──────────────────────────────────────────────┐
│                  Browser (Vue.js)            │
│         Inertia.js — SPA Navigation          │
└──────────────────┬───────────────────────────┘
                   │ HTTP / Inertia XHR
┌──────────────────▼───────────────────────────┐
│             Laravel Backend                  │
│  ┌────────────┐    ┌───────────────────────┐ │
│  │   Routes   │ →  │    Controllers        │ │
│  └────────────┘    │  (Http/Controllers)   │ │
│                    └──────────┬────────────┘ │
│                               │              │
│                    ┌──────────▼────────────┐ │
│                    │    Services Layer      │ │
│                    │  (app/Services)        │ │
│                    └──────────┬────────────┘ │
│                               │              │
│  ┌───────────────┐  ┌─────────▼───────────┐ │
│  │   Queue/Jobs  │  │   Eloquent Models   │ │
│  │ (Notifikasi)  │  │   (app/Models)      │ │
│  └───────────────┘  └─────────┬───────────┘ │
└────────────────────────────── │ ─────────────┘
                                │
              ┌─────────────────▼──────────────┐
              │      MySQL (dev & production)  │
              │  SQLite :memory: (testing only)│
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
