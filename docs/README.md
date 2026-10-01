# 📘 PJH-ERP — Dokumentasi Sistem

Selamat datang di dokumentasi lengkap **PJH-ERP**, sistem manajemen sumber daya perusahaan yang dibangun di atas Laravel 12.

## 📂 Daftar Dokumen

| Dokumen | Deskripsi |
|---|---|
| [01 — Arsitektur Sistem](./01-architecture.md) | Stack teknologi, struktur direktori, dan arsitektur keseluruhan (Web + Mobile) |
| [02 — Modul Sistem](./02-modules.md) | Penjelasan tiap modul: System, HR (termasuk Absensi), Finance, ESS |
| [03 — Alur Persetujuan (ApprovalService)](./03-approval-workflow.md) | Mekanisme approval bertingkat, auto-skip, identity guard |
| [04 — Model & Database](./04-models-database.md) | Schema tabel utama, relasi antar model |
| [05 — Routes & Otorisasi](./05-routes-authorization.md) | Struktur routing web admin, role system, dan REST API routes mobile |
| [06 — Services Layer](./06-services.md) | Semua service class termasuk AbsensiService |
| [07 — Enums & Konfigurasi](./07-enums-config.md) | Enum yang digunakan dan konfigurasi sistem |
| [08 — Testing](./08-testing.md) | Panduan dan daftar test yang ada |
| [09 — Panduan Pengembang](./09-developer-guide.md) | Setup lokal, konvensi kode, cara menambah fitur, Mobile API guide |
| [10 — Mobile API](./10-mobile-api.md) | **Spesifikasi lengkap REST API** untuk Flutter: Auth, Dashboard (BFF), Absensi, Cuti |

---

> Dokumentasi ini terakhir diperbarui: **Oktober 2026** (ditambahkan: Mobile API, Absensi, BFF Pattern, AbsensiService, Role Karyawan).
