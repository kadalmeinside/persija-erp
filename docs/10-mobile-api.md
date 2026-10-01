# 10 — Mobile API (REST API untuk Flutter)

> **Base URL:** `https://erp.persijadevelopment.id/api/v1`  
> **Authentication:** `Authorization: Bearer {token}` (Laravel Sanctum)  
> **Content-Type Default:** `application/json`  
> **Upload Endpoint:** `multipart/form-data`

---

## Panduan Untuk Mobile Developer

### Alur Integrasi Umum

```
1. Login → Simpan token di Secure Storage (FlutterSecureStorage)
2. Buka Home → Panggil GET /dashboard/home (SATU request)
3. Absen Masuk → POST /absensi/clock-in (GPS + foto selfie)
4. Absen Pulang → POST /absensi/clock-out (GPS + foto selfie)
5. Ajukan Cuti → POST /cuti/request (form + lampiran optional)
```

### Error Handling Global

Semua endpoint mengembalikan JSON bahkan saat error (tidak pernah HTML):

| HTTP Code | Arti |
|---|---|
| `200` | Sukses |
| `201` | Created (data berhasil dibuat) |
| `401` | Token tidak valid / expired → redirect ke Login |
| `403` | Akses ditolak (karyawan tidak terhubung ke data karyawan) |
| `422` | Validasi gagal (`errors` berisi detail field) |
| `500` | Server error |

**Contoh response error validasi (422):**
```json
{
  "message": "The latitude field is required.",
  "errors": {
    "latitude": ["The latitude field is required."]
  }
}
```

---

## 1. Auth Module

### 1.1 Login

- **Endpoint:** `POST /login`
- **Auth:** Public
- **Payload:**
```json
{
  "email": "aris.1@persijadevelopment.id",
  "password": "persija123"
}
```
- **Response (200 OK):**
```json
{
  "data": {
    "token": "1|abcdefghijklmnopqrstuvwxyz",
    "user": {
      "id": 5,
      "name": "Aris Budiman",
      "email": "aris.1@persijadevelopment.id",
      "role": "Karyawan"
    }
  }
}
```

> ⚠️ **Simpan `token` di Secure Storage.** Sertakan di semua request berikutnya sebagai `Authorization: Bearer {token}`.

### 1.2 Get Profile

- **Endpoint:** `GET /user`
- **Auth:** Bearer Token
- **Response (200 OK):**
```json
{
  "data": {
    "id": 5,
    "name": "Aris Budiman",
    "email": "aris.1@persijadevelopment.id",
    "karyawan": {
      "id": 10,
      "nomor_induk_karyawan": "P001",
      "nama_lengkap": "Aris Budiman",
      "jabatan": "Security",
      "tgl_bergabung": "2023-01-15",
      "status_karyawan": "Tetap",
      "foto_url": "https://...",
      "is_strict_location": false,
      "lokasi_kantor": {
        "nama_kantor": "Kantor Pusat",
        "latitude": "-6.38244709",
        "longitude": "106.73988735",
        "radius": 100
      }
    }
  }
}
```

### 1.3 Logout

- **Endpoint:** `POST /logout`
- **Auth:** Bearer Token
- **Response (200 OK):**
```json
{ "message": "Successfully logged out" }
```

### 1.4 Update Password

- **Endpoint:** `POST /update-password`
- **Auth:** Bearer Token
- **Payload:**
```json
{
  "current_password": "persija123",
  "new_password": "password_baru_123",
  "new_password_confirmation": "password_baru_123"
}
```

---

## 2. Dashboard (BFF Pattern)

> **PENTING:** Gunakan endpoint ini di Home Screen. **Jangan** panggil `/user`, `/absensi/today`, dan `/cuti/balances` secara terpisah di halaman utama.

### 2.1 Home Dashboard

- **Endpoint:** `GET /dashboard/home`
- **Auth:** Bearer Token
- **Response (200 OK):**
```json
{
  "data": {
    "user": {
      "name": "Aris Budiman",
      "jabatan": "Security",
      "foto_url": "https://erp.persijadevelopment.id/storage/..."
    },
    "attendance_today": {
      "status": "Hadir",
      "jam_masuk": "07:55:12",
      "jam_keluar": null
    },
    "leave_balance": {
      "annual_leave_remaining": 8,
      "total_annual_leave": 12
    },
    "tasks": {
      "pending_approvals": 0
    }
  }
}
```

**Mapping ke UI Flutter:**

| Field | Tampilkan di |
|---|---|
| `user.name` | Header/AppBar greeting |
| `user.foto_url` | Avatar karyawan |
| `attendance_today.status` | Status card absensi |
| `attendance_today.jam_masuk` | Jam masuk di status card |
| `leave_balance.annual_leave_remaining` | Widget saldo cuti tahunan |
| `tasks.pending_approvals` | Badge notifikasi jika > 0 |

---

## 3. Absensi (Attendance) Module

> Gunakan **GPS dari native OS** (bukan `geolocator` saja). Pastikan `LocationAccuracy.high` untuk mencegah false GPS.

### 3.1 Cek Status Hari Ini

- **Endpoint:** `GET /absensi/today`
- **Auth:** Bearer Token
- **Gunakan:** Hanya jika perlu refresh status absensi secara spesifik (tanpa reload seluruh dashboard)
- **Response (200 OK):**
```json
{
  "data": {
    "clock_in": "07:55:00",
    "clock_out": null,
    "status": "Hadir"
  }
}
```

### 3.2 Clock-In (Absen Masuk)

- **Endpoint:** `POST /absensi/clock-in`
- **Auth:** Bearer Token
- **Content-Type:** `multipart/form-data`
- **Payload:**

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `latitude` | float | ✅ | Koordinat GPS saat ini |
| `longitude` | float | ✅ | Koordinat GPS saat ini |
| `photo` | file (image) | ✅ | Foto selfie (max 2MB) |
| `is_dinas_luar` | boolean | ❌ | `true` jika bekerja di luar kantor |
| `catatan` | string | ⚠️ | Wajib jika `is_dinas_luar = true` |

- **Response (201 Created):**
```json
{
  "message": "Berhasil absen masuk pada 08:00:15",
  "data": {
    "id": 100,
    "clock_in": "08:00:15"
  }
}
```
- **Error (422):** Lokasi di luar radius / GPS tidak valid / sudah absen masuk hari ini

### 3.3 Clock-Out (Absen Pulang)

- **Endpoint:** `POST /absensi/clock-out`
- **Auth:** Bearer Token
- **Content-Type:** `multipart/form-data`
- **Payload:** Sama dengan Clock-In (`latitude`, `longitude`, `photo`)
- **Response (200 OK):** Sama dengan Clock-In

### 3.4 Riwayat Absensi

- **Endpoint:** `GET /absensi/history`
- **Auth:** Bearer Token
- **Query Params:** `?month=10&year=2026&page=1`
- **Response (200 OK):**
```json
{
  "data": [
    {
      "id": 8,
      "tanggal": "2026-10-01",
      "jam_masuk": "07:50:00",
      "jam_keluar": "17:05:00",
      "status_kehadiran": "Hadir",
      "is_dinas_luar": false,
      "catatan": null,
      "foto_masuk": "https://erp.persijadevelopment.id/storage/absensi/foto.jpg"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 10,
    "total": 23
  },
  "links": {
    "next": "https://.../absensi/history?page=2"
  }
}
```

> **Implementasi Infinite Scroll:** Gunakan `ScrollController`. Ketika user scroll ke bawah dan `current_page < last_page`, panggil `page+1` dan append data ke list.

---

## 4. Cuti (Leave Request) Module

### 4.1 Jenis Cuti (Dropdown)

- **Endpoint:** `GET /cuti/jenis`
- **Auth:** Bearer Token
- **Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "nama_cuti": "Cuti Tahunan",
      "kuota_default": 12,
      "wajib_lampiran": false,
      "bisa_mundur": true,
      "khusus_perempuan": false,
      "is_unlimited": false
    }
  ]
}
```

> Gunakan `wajib_lampiran` untuk menentukan apakah field upload lampiran **wajib** ditampilkan di form pengajuan cuti.

### 4.2 Saldo Cuti

- **Endpoint:** `GET /cuti/balances`
- **Auth:** Bearer Token
- **Response (200 OK):**
```json
{
  "data": [
    {
      "id": 5,
      "jenis_cuti_id": 1,
      "jenis_cuti": "Cuti Tahunan",
      "saldo_awal": 12,
      "saldo_terpakai": 4,
      "saldo_akhir": 8
    }
  ]
}
```

### 4.3 Riwayat Pengajuan Cuti

- **Endpoint:** `GET /cuti/requests`
- **Auth:** Bearer Token
- **Response (200 OK):**
```json
{
  "data": [
    {
      "id": 3,
      "jenis_cuti": "Cuti Tahunan",
      "tgl_mulai": "2026-10-05",
      "tgl_selesai": "2026-10-07",
      "jumlah_hari": 3,
      "alasan": "Liburan keluarga",
      "status": "Pending",
      "lampiran": null,
      "created_at": "2026-10-01 09:30:00"
    }
  ]
}
```

### 4.4 Buat Pengajuan Cuti

- **Endpoint:** `POST /cuti/request`
- **Auth:** Bearer Token
- **Content-Type:** `multipart/form-data` (karena ada kemungkinan upload file)
- **Payload:**

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `jenis_cuti_id` | int | ✅ | ID dari `GET /cuti/jenis` |
| `tgl_mulai` | string (YYYY-MM-DD) | ✅ | Tanggal mulai cuti |
| `tgl_selesai` | string (YYYY-MM-DD) | ✅ | Tanggal selesai cuti |
| `keterangan` | string | ✅ | Alasan cuti |
| `attachment` | file | ⚠️ | Wajib jika `wajib_lampiran = true` |

- **Response (201 Created):**
```json
{
  "message": "Pengajuan cuti berhasil dibuat dan menunggu persetujuan.",
  "data": { ... }
}
```

---

## 5. Task (Kanban) Module

### 5.1 List Active Tasks (Kanban Board)

- **Endpoint:** `GET /tasks`
- **Auth:** Bearer Token
- **Deskripsi:** Mengambil semua tugas aktif yang terkait dengan user saat ini (baik sebagai creator maupun assignee).
- **Response (200 OK):**
```json
{
  "success": true,
  "message": "Berhasil mengambil data tugas aktif",
  "data": [
    {
      "id": 1,
      "title": "Perbaiki UI Dashboard",
      "description": "Sesuaikan warna dengan tema baru",
      "priority": "High",
      "status": "To Do",
      "due_date": "2026-10-15",
      "order_index": 0,
      "id_karyawan_creator": 1,
      "id_karyawan_assignee": 5,
      "creator": {
        "id": 1,
        "nama_lengkap": "System Admin"
      },
      "assignee": {
        "id": 5,
        "nama_lengkap": "Aris Budiman"
      }
    }
  ]
}
```

### 5.2 Task History (Tugas Aktif & Arsip)

- **Endpoint:** `GET /tasks/history?page=1`
- **Auth:** Bearer Token
- **Deskripsi:** Mengambil riwayat tugas dengan pagination (cocok untuk Table/List View di Mobile).
- **Response (200 OK):**
```json
{
  "success": true,
  "message": "Berhasil mengambil riwayat tugas",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 2,
        "title": "Buat Laporan Bulanan",
        "status": "Done",
        "archived_at": "2026-10-01T15:00:00.000000Z"
      }
    ],
    "last_page": 2
  }
}
```

### 5.3 Buat Tugas Baru

- **Endpoint:** `POST /tasks`
- **Auth:** Bearer Token
- **Payload:**
```json
{
  "title": "Review Kontrak Sponsor",
  "description": "Harap cek draft kontrak PT XYZ",
  "priority": "High",
  "due_date": "2026-10-10",
  "id_karyawan_assignee": 5
}
```
> *Catatan: Jika `id_karyawan_assignee` dikosongkan, tugas akan di-assign ke pembuat tugas secara otomatis.*

### 5.4 Update Status / Arsipkan Tugas

- **Endpoint:** `POST /tasks/{id}/status`
- **Auth:** Bearer Token
- **Payload:**
```json
{
  "status": "In Progress",
  "archive": false
}
```
> *Catatan: Set `"archive": true` untuk mengarsipkan tugas.*

---

## 6. Troubleshooting Mobile API

| Masalah | Penyebab | Solusi |
|---|---|---|
| `401 Unauthorized` di semua request | Token expired / tidak valid | Hapus token dari storage, redirect ke Login |
| `403 Forbidden` setelah login | Akun user tidak terhubung ke data Karyawan | HR perlu menghubungkan user ke data karyawan di Admin Panel |
| `422` saat clock-in: "Lokasi diluar radius" | GPS user di luar area kantor | Pastikan karyawan berada di radius kantor atau aktifkan `is_dinas_luar` |
| `422` saat clock-in: "Anda sudah absen masuk" | Duplikat clock-in di hari yang sama | Tampilkan tombol Clock-Out, bukan Clock-In |
| Jam absensi `null` meski sudah clock-in | Nama kolom tidak sesuai | Gunakan `jam_masuk` / `jam_keluar` dari response (bukan `waktu_masuk`) |
| Password default tidak bisa login | Password belum diganti HR | Password default: `persija123`, PIN default: `123456` |

---

## 7. Catatan Khusus untuk Developer

### Pembuatan Akun Karyawan
Akun login karyawan **tidak dibuat otomatis saat import**. HR membuat akun secara selektif melalui:
- Tombol **"Buat Akun Login"** di halaman Detail Karyawan (Web Admin)
- Atau command: `php artisan user:generate-for-karyawan` (untuk generate masal)

**Format email otomatis:** `{namadepan}.{id_karyawan}@persijadevelopment.id`  
**Password default:** `persija123`  
**PIN default:** `123456`  
**Role yang diberikan:** `Karyawan`

### Dashboard BFF Pattern
Saat membuka Home Screen, **hanya panggil SATU endpoint**:
```dart
final response = await dio.get('/api/v1/dashboard/home');
// Dari response ini, populate SEMUA widget di Home Screen
```

**Jangan lakukan ini** (anti-pattern):
```dart
// ❌ 3 request terpisah = loading lambat
final profile = await dio.get('/api/v1/user');
final absensi = await dio.get('/api/v1/absensi/today');
final cuti = await dio.get('/api/v1/cuti/balances');
```
