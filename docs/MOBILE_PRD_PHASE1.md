# Product Requirements Document (PRD) - Mobile ESS (Phase 1)
**Project Name:** Persija ERP Enterprise Mobile App
**Platform:** Android & iOS (Native via Flutter)
**Version:** 1.0.0 (Phase 1)
**Base API URL (Production):** `https://your-domain.com/api/v1`
**Base API URL (Development):** `http://192.168.x.x:8000/api/v1`

---

## 1. Executive Summary
Aplikasi mobile ini (Employee Self-Service) dirancang secara spesifik untuk memfasilitasi karyawan dalam melakukan presensi harian secara aman dan mengajukan cuti. Tujuan utama pemisahan absensi dari web ke *mobile native* adalah untuk menekan tingkat kecurangan (seperti penggunaan *Fake GPS* atau absen titip) melalui pelacakan lokasi *hardware* perangkat dan pengambilan *selfie* langsung dari kamera HP. Desain antarmuka (UI) akan secara konsisten mengikuti arsitektur visual versi Web Persija ERP.

---

## 2. Tech Stack & Architecture

### 2.1. Dependencies (pubspec.yaml)
```yaml
dependencies:
  flutter:
    sdk: flutter
  # Networking
  dio: ^5.x
  # Secure Token Storage
  flutter_secure_storage: ^9.x
  # Shared Preferences (non-sensitive)
  shared_preferences: ^2.x
  # Location / GPS
  geolocator: ^11.x
  # Camera & Image Picker
  image_picker: ^1.x
  camera: ^0.10.x
  # Security
  freerasp: ^6.x
  # UI
  google_fonts: ^6.x
  shimmer: ^3.x
  # State Management (pilih salah satu)
  flutter_riverpod: ^2.x      # Pilihan 1: Riverpod
  # flutter_bloc: ^8.x        # Pilihan 2: BLoC
```

### 2.2. Arsitektur Folder Project Flutter
```
lib/
├── core/
│   ├── constants/          # app_colors.dart, app_strings.dart, api_constants.dart
│   ├── network/            # dio_client.dart, auth_interceptor.dart
│   ├── security/           # security_checker.dart (Anti-fake GPS, root detection)
│   └── storage/            # secure_storage.dart
├── features/
│   ├── auth/
│   │   ├── data/           # auth_repository.dart, auth_api.dart
│   │   ├── models/         # user_model.dart, karyawan_model.dart
│   │   └── screens/        # login_screen.dart
│   ├── dashboard/
│   │   └── screens/        # dashboard_screen.dart
│   ├── absensi/
│   │   ├── data/           # absensi_repository.dart, absensi_api.dart
│   │   ├── models/         # absensi_model.dart
│   │   └── screens/        # absensi_screen.dart, camera_screen.dart, location_check_screen.dart
│   └── cuti/
│       ├── data/           # cuti_repository.dart, cuti_api.dart
│       ├── models/         # cuti_model.dart, saldo_cuti_model.dart, jenis_cuti_model.dart
│       └── screens/        # cuti_screen.dart, form_cuti_screen.dart, histori_cuti_screen.dart
└── main.dart
```

### 2.3. Konfigurasi Environment
```dart
// lib/core/constants/api_constants.dart
class ApiConstants {
  // Ganti nilai ini saat build production
  static const String baseUrl = String.fromEnvironment(
    'BASE_URL',
    defaultValue: 'http://192.168.1.1:8000/api/v1',
  );
}
// Build production: flutter build apk --dart-define=BASE_URL=https://erp.persija.id/api/v1
```

---

## 3. Detail UI/UX & Desain Antarmuka
Aplikasi wajib menggunakan palet warna utama (Identitas Persija) dan *layout* bergaya modern *enterprise*. Desain harus identik dengan rasa (feel) versi Web Tailwind CSS.

### 3.1. Color Palette & Typography
| Token           | Hex       | Penggunaan                                        |
|-----------------|-----------|---------------------------------------------------|
| Primary         | `#DC2626` | Tombol utama, active state, header sidebar        |
| Primary Dark    | `#B91C1C` | Hover/Pressed state tombol primary                |
| Success         | `#10B981` | Tombol "Absen Masuk", badge Hadir/Disetujui       |
| Danger          | `#EF4444` | Tombol "Absen Pulang", badge Ditolak/Alpa         |
| Warning         | `#F59E0B` | Badge "Pending"                                   |
| Text Primary    | `#1F2937` | Judul & teks utama                                |
| Text Secondary  | `#6B7280` | Subteks, placeholder                              |
| Background      | `#F3F4F6` | Latar belakang layar                              |
| Surface (Card)  | `#FFFFFF` | Latar Card, Form Field                            |
| Border          | `#E5E7EB` | Garis border Card & Input                         |

- **Font:** `Inter` atau `Roboto` (via `google_fonts`).
- **Heading:** `FontWeight.w700`, size 18–22sp.
- **Body:** `FontWeight.w400`, size 14sp.
- **Caption/Label:** `FontWeight.w500`, size 12sp.
- **Border Radius Card/Button:** `12.0` px.
- **Elevation/Shadow Card:** `BoxShadow(blurRadius: 8, color: Colors.black12)`.

### 3.2. Struktur Navigasi (Hamburger Menu & App Bar)
```
┌─────────────────────────────────────────────────┐
│  ☰ (Hamburger)   Judul Halaman   🔔   [Avatar]  │  ← App Bar (putih, border bawah abu)
├─────────────────────────────────────────────────┤
│                  Konten Halaman                 │
└─────────────────────────────────────────────────┘

Side Drawer (Muncul dari Kiri):
┌──────────────────────┐
│  [Logo Persija]      │  ← Header bg #DC2626
│  John Doe            │
│  NIP: 12345 | IT     │
├──────────────────────┤
│  🏠  Dashboard       │  ← ListTile, icon abu, text #1F2937
│  📍  Absensi         │
│  📅  Cuti            │
│  ─────────────────── │
│  🚪  Keluar (Merah)  │  ← Teks & ikon #DC2626
└──────────────────────┘
```
- Menu aktif: Latar merah muda (`#FEE2E2`) + teks merah + ikon merah.
- Menu tidak aktif: Teks abu gelap + ikon abu.

---

## 4. Detail Fitur & Spesifikasi Halaman (Phase 1)

### 4.1. Halaman Login (Login Screen)
```
┌─────────────────────────────────┐
│                                 │
│      [Logo Persija]             │  ← Center, ukuran 120x120
│   "Persija ERP"  (Bold, 22sp)  │
│   "Employee Self-Service"       │
│                                 │
│  ┌─────────────────────────┐   │
│  │  📧  Email              │   │  ← Outlined TextField
│  └─────────────────────────┘   │
│  ┌─────────────────────────┐   │
│  │  🔒  Password        👁 │   │  ← Outlined TextField + toggle show/hide
│  └─────────────────────────┘   │
│                                 │
│  [        MASUK        ]        │  ← ElevatedButton, bg #DC2626, w: 100%, h: 52px
│                                 │
│  v1.0.0 (Phase 1)              │  ← Caption, abu, paling bawah
└─────────────────────────────────┘
```
- Error login: Snackbar merah di bawah dengan pesan dari API.
- Loading state: Tombol menampilkan `CircularProgressIndicator` putih.

### 4.2. Halaman Dashboard (Home)
```
┌─────────────────────────────────┐
│  ☰  Dashboard         🔔 [Foto]│  ← App Bar
├─────────────────────────────────┤
│  ┌───────────────────────────┐  │
│  │  Selamat Datang, John! 👋 │  │  ← Card putih, padding 16
│  │  Staff IT | Departemen IT │  │
│  └───────────────────────────┘  │
│                                 │
│  ┌───────────────────────────┐  │
│  │  📍 Kehadiran Hari Ini    │  │  ← Card putih
│  │  Masuk: 08:00  Pulang: -- │  │
│  │  ─────────────────────── │  │
│  │  [  🟢 ABSEN MASUK  ]    │  │  ← Tombol Hijau #10B981 (jika belum masuk)
│  │  [  🔴 ABSEN PULANG ]    │  │  ← Tombol Merah #EF4444 (jika sudah masuk)
│  └───────────────────────────┘  │
│                                 │
│  Quick Actions                  │
│  ┌──────┐  ┌──────┐  ┌──────┐  │
│  │  📅  │  │  📋  │  │  ⏱  │  │  ← Grid 3 kolom: Ajukan Cuti, Histori, Jam Kerja
│  │ Cuti │  │Histor│  │ Jam  │  │
│  └──────┘  └──────┘  └──────┘  │
└─────────────────────────────────┘
```

### 4.3. Alur Absensi (3 Layar/Step)
**Step 1 – Cek Lokasi:**
```
┌─────────────────────────────────┐
│  ←  Absen Masuk                 │
├─────────────────────────────────┤
│  [   Peta / Map View   ]        │  ← Tampilan peta kecil (atau hanya ikon GPS besar)
│  📍 Lokasi Anda: Terdeteksi     │
│  🏢 Kantor Pusat (50m radius)   │
│                                 │
│  ✅ Anda dalam radius kantor    │  ← Teks hijau
│  ❌ Anda di luar radius! (merah)│  ← Muncul jika di luar radius
│                                 │
│  ┌──────────────────────────┐   │
│  │ ☐ Sedang Dinas Luar      │   │  ← Checkbox
│  └──────────────────────────┘   │
│  (Jika dicentang, muncul:)      │
│  ┌──────────────────────────┐   │
│  │ Catatan lokasi dinas...  │   │  ← TextField WAJIB
│  └──────────────────────────┘   │
│                                 │
│  [   LANJUT KE KAMERA   ]      │  ← Disabled jika diluar radius & bukan dinas luar
└─────────────────────────────────┘
```
**Step 2 – Selfie:**
```
┌─────────────────────────────────┐
│  ←  Ambil Selfie                │
├─────────────────────────────────┤
│                                 │
│   ┌───────────────────────┐     │
│   │   [Preview Kamera]    │     │  ← Circle crop frame di tengah
│   │     (Kamera Depan)    │     │
│   └───────────────────────┘     │
│                                 │
│  "Pastikan wajah terlihat jelas"│
│                                 │
│           [ 📷 ]                │  ← ShutterButton, bulat, merah, di bawah tengah
│    (Galeri DILARANG di sini)    │
└─────────────────────────────────┘
```
**Step 3 – Konfirmasi:**
```
┌─────────────────────────────────┐
│  ←  Konfirmasi Absen            │
├─────────────────────────────────┤
│  [Preview Foto Selfie]          │
│                                 │
│  📍 Lat: -6.2088  Lng: 106.845  │
│  🕐 Waktu: 08:00 WIB            │
│  📍 Lokasi: Kantor Pusat        │
│                                 │
│  [   KIRIM ABSENSI   ]          │  ← Merah #DC2626
└─────────────────────────────────┘
```

### 4.4. Halaman Cuti
```
┌─────────────────────────────────┐
│  ☰  Cuti                🔔 [F] │
├─────────────────────────────────┤
│  Saldo Cuti Saya                │
│  ┌──────────┐  ┌─────────────┐  │
│  │ Tahunan  │  │ Sakit       │  │  ← Card kecil per jenis cuti
│  │ Sisa: 10 │  │ Sisa: 5     │  │
│  └──────────┘  └─────────────┘  │
│                                 │
│  [ +  AJUKAN CUTI  ]            │  ← ElevatedButton Merah
│                                 │
│  Riwayat Pengajuan              │
│  ┌───────────────────────────┐  │
│  │ Cuti Tahunan              │  │  ← Card ListTile
│  │ 1 Okt – 2 Okt 2026 (2hr) │  │
│  │                 [Pending] │  │  ← Badge kuning
│  └───────────────────────────┘  │
└─────────────────────────────────┘
```
- **Form Pengajuan Cuti:**
  - `DropdownButtonFormField` Jenis Cuti
  - `TextFormField` Tanggal Mulai (DatePicker, format dd/MM/yyyy)
  - `TextFormField` Tanggal Selesai (DatePicker)
  - Kalkulasi otomatis "Jumlah hari: X hari" setelah tanggal dipilih
  - `TextFormField` (multiline) Alasan/Keterangan
  - Kotak Upload Lampiran (Dashed Border, muncul hanya jika `wajib_lampiran: true`)
  - Tombol "Kirim" full-width merah di bawah

---

## 5. Security & Anti-Fraud Specifications (CRITICAL)

### 5.1. Anti-Mock Location (Fake GPS)
```dart
// lib/core/security/security_checker.dart

import 'package:geolocator/geolocator.dart';

Future<bool> isMockLocation(Position position) async {
  // Android: isMocked property di Position object
  if (position.isMocked) {
    return true;
  }
  return false;
}
```
- Jika `isMocked == true`: **Tampilkan dialog peringatan keras** dan **batalkan proses absen**.
- Jangan beri tahu user cara "bypass" — cukup tampilkan: *"GPS Anda tidak valid. Harap nonaktifkan aplikasi pemalsuan lokasi."*

### 5.2. Root & Jailbreak Detection (`freerasp`)
```dart
// Inisialisasi di main.dart atau sebelum LayarLogin
final config = TalsecConfig(
  androidConfig: AndroidConfig(
    packageName: 'id.persija.erp.mobile',
    signingCertHashes: ['your_signing_cert_hash_here'],
    supportedStores: [PlayStore()],
  ),
  iosConfig: IOSConfig(
    bundleIds: ['id.persija.erp.mobile'],
    teamId: 'YOUR_TEAM_ID',
  ),
  watcherMail: 'security@persija.id',
);
// Jika terdeteksi Root/Jailbreak -> keluar paksa dari aplikasi
```

### 5.3. Emulator Blocking
- `freerasp` sudah mendeteksi emulator secara otomatis.
- Tambahkan flag manual: periksa apakah `Platform.isAndroid` dan `DeviceInfoPlugin` melaporkan `isPhysicalDevice == false`.

### 5.4. No Gallery for Absensi
```dart
// Saat absen, HANYA gunakan camera source
final XFile? photo = await ImagePicker().pickImage(
  source: ImageSource.camera,       // ← WAJIB, bukan ImageSource.gallery
  preferredCameraDevice: CameraDevice.front, // Kamera depan
  imageQuality: 70,                 // Kompres untuk hemat bandwidth
);
```

### 5.5. Permissions yang Dibutuhkan
**Android (`AndroidManifest.xml`):**
```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
<uses-permission android:name="android.permission.CAMERA" />
<uses-feature android:name="android.hardware.camera" android:required="true" />
```
**iOS (`Info.plist`):**
```xml
<key>NSLocationWhenInUseUsageDescription</key>
<string>Aplikasi membutuhkan akses lokasi untuk verifikasi presensi.</string>
<key>NSCameraUsageDescription</key>
<string>Aplikasi membutuhkan kamera untuk selfie presensi.</string>
<key>NSPhotoLibraryUsageDescription</key>
<string>Digunakan untuk upload lampiran cuti.</string>
```

---

## 6. API Structure Reference (V1)
Semua koneksi ke backend harus menggunakan `https` (Wajib SSL).
Setiap *request* yang membutuhkan autentikasi harus mengirimkan header:
```
Authorization: Bearer {token}
Accept: application/json
```

### 6.0. Standard Error Response Format
Semua error API menggunakan format konsisten berikut:
```json
// 401 Unauthorized (Token expired / tidak valid)
{ "message": "Unauthenticated." }

// 403 Forbidden
{ "message": "Anda tidak memiliki akses untuk melihat data ini." }

// 422 Unprocessable Entity (Validasi gagal)
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}

// 404 Not Found
{ "message": "Pengajuan tidak ditemukan." }

// 500 Server Error
{ "message": "Server Error" }
```
**Penanganan di Dio Interceptor:**
```dart
// lib/core/network/auth_interceptor.dart
onError: (error, handler) {
  if (error.response?.statusCode == 401) {
    // Token expired: Hapus token, redirect ke Login
    SecureStorage().deleteToken();
    Get.offAll(() => LoginScreen());
  }
  handler.next(error);
}
```

---

### 6.1. Authentication Module

**POST `/api/v1/login`** — Public (Tanpa token)
- **Header:** `Content-Type: application/json`
- **Payload (JSON):**
  ```json
  {
    "email": "user@persija.id",
    "password": "password123"
  }
  ```
- **Response 200 OK:**
  ```json
  {
    "data": {
      "token": "1|xyz123abc...",
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@persija.id",
        "role": "Staf"
      }
    }
  }
  ```
- **Response 401:** `{ "message": "Email atau password salah." }`
- **Response 422:** `{ "message": "...", "errors": { ... } }`

---

**GET `/api/v1/user`** — Profil & Geofencing Data
- **Tujuan:** Mendapatkan metadata profil + parameter Geofencing untuk mengunci tombol absen.
- **Response 200 OK:**
  ```json
  {
    "data": {
      "id": 1,
      "name": "John Doe",
      "email": "user@persija.id",
      "role": "Staf",
      "karyawan": {
        "nip": "12345",
        "nama_lengkap": "John Doe",
        "jabatan": "Staff IT",
        "departemen": "IT",
        "foto": "https://domain.com/storage/foto/karyawan.jpg",
        "is_strict_location": true,
        "lokasi_kantor": {
          "nama": "Kantor Pusat",
          "latitude": -6.2088,
          "longitude": 106.8456,
          "radius": 50
        }
      }
    }
  }
  ```
  > ⚠️ Jika `is_strict_location: false` atau `lokasi_kantor: null`, abaikan validasi radius.

---

**POST `/api/v1/logout`** — Menghanguskan Token
- **Payload:** Tidak diperlukan (Token dibaca dari header `Authorization`).
- **Response 200 OK:** `{ "message": "Successfully logged out" }`

---

### 6.2. Absensi (Attendance) Module

**GET `/api/v1/absensi/today`** — Status Absen Hari Ini
- **Tujuan:** Menentukan tombol mana yang ditampilkan di Dashboard (Absen Masuk atau Absen Pulang).
- **Response 200 OK:**
  ```json
  {
    "data": {
      "clock_in": "08:00:00",
      "clock_out": null,
      "status": "Hadir"
    }
  }
  ```
  > Jika belum absen sama sekali: `clock_in: null`, `clock_out: null`, `status: "Belum Absen"`.

---

**POST `/api/v1/absensi/clock-in`** — Absen Masuk
- **Header:** `Content-Type: multipart/form-data`
- **Payload (Form Data):**

  | Field          | Tipe          | Wajib    | Keterangan                          |
  |----------------|---------------|----------|-------------------------------------|
  | `latitude`     | Numeric       | ✅ Ya    | Koordinat GPS saat ini              |
  | `longitude`    | Numeric       | ✅ Ya    | Koordinat GPS saat ini              |
  | `photo`        | File (Image)  | ❌ Tidak | Foto selfie (jpeg/png, max 2MB)     |
  | `is_dinas_luar`| Integer (0/1) | ❌ Tidak | `1` jika sedang dinas luar          |
  | `catatan`      | String        | ❌ Tidak | Wajib jika `is_dinas_luar = 1`      |

- **Response 201 Created:**
  ```json
  {
    "message": "Berhasil absen masuk.",
    "data": {
      "id": 10,
      "clock_in": "08:00:00"
    }
  }
  ```
- **Response 422:** `{ "message": "Anda sudah melakukan absen masuk hari ini." }`

---

**POST `/api/v1/absensi/clock-out`** — Absen Pulang
- **Header:** `Content-Type: multipart/form-data`
- **Payload (Form Data):**

  | Field       | Tipe          | Wajib | Keterangan                      |
  |-------------|---------------|-------|---------------------------------|
  | `latitude`  | Numeric       | ✅ Ya | Koordinat GPS saat ini          |
  | `longitude` | Numeric       | ✅ Ya | Koordinat GPS saat ini          |
  | `photo`     | File (Image)  | ❌    | Foto selfie (jpeg/png, max 2MB) |

- **Response 200 OK:**
  ```json
  {
    "message": "Berhasil absen pulang.",
    "data": {
      "id": 10,
      "clock_out": "17:00:00"
    }
  }
  ```
- **Response 422:** `{ "message": "Anda belum absen masuk hari ini." }`

---

**GET `/api/v1/absensi/history?month=09&year=2026`** — Histori Absensi Bulanan
- **Query Params:** `month` (01–12), `year` (YYYY). Default ke bulan & tahun berjalan.
- **Response 200 OK:**
  ```json
  {
    "data": [
      {
        "id": 10,
        "date": "2026-09-25",
        "clock_in": "08:00:00",
        "clock_out": "17:00:00",
        "status": "Hadir"
      },
      {
        "id": 11,
        "date": "2026-09-24",
        "clock_in": null,
        "clock_out": null,
        "status": "Alpa"
      }
    ]
  }
  ```

---

### 6.3. Cuti (Leave) Module

**GET `/api/v1/cuti/jenis`** — Dropdown Jenis Cuti
- **Tujuan:** Mengisi dropdown pilihan di form pengajuan cuti.
- **Response 200 OK:**
  ```json
  {
    "data": [
      {
        "id": 1,
        "nama_cuti": "Cuti Tahunan",
        "kuota_default": 12,
        "wajib_lampiran": false
      },
      {
        "id": 2,
        "nama_cuti": "Cuti Sakit",
        "kuota_default": 12,
        "wajib_lampiran": true
      }
    ]
  }
  ```
  > Jika `wajib_lampiran: true`, tampilkan area upload surat dokter di form.

---

**GET `/api/v1/cuti/balances`** — Saldo Cuti Berjalan
- **Response 200 OK:**
  ```json
  {
    "data": [
      {
        "id": 1,
        "jenis_cuti_id": 1,
        "jenis_cuti": "Cuti Tahunan",
        "saldo_awal": 12,
        "saldo_terpakai": 2,
        "saldo_akhir": 10
      }
    ]
  }
  ```

---

**GET `/api/v1/cuti/requests`** — Histori Pengajuan Cuti
- **Response 200 OK:**
  ```json
  {
    "data": [
      {
        "id": 5,
        "jenis_cuti": "Cuti Tahunan",
        "tgl_mulai": "2026-10-01",
        "tgl_selesai": "2026-10-02",
        "jumlah_hari": 2,
        "alasan": "Urusan keluarga",
        "status": "Pending",
        "lampiran": null,
        "created_at": "2026-09-27 08:00:00"
      }
    ]
  }
  ```
  > Nilai `status` bisa: `"Pending"` | `"Approved"` | `"Rejected"`

---

**POST `/api/v1/cuti/request`** — Submit Pengajuan Cuti
- **Header:** `Content-Type: multipart/form-data`
- **Payload (Form Data):**

  | Field           | Tipe         | Wajib    | Keterangan                                          |
  |-----------------|--------------|----------|-----------------------------------------------------|
  | `jenis_cuti_id` | Integer      | ✅ Ya    | ID dari endpoint `/cuti/jenis`                      |
  | `tgl_mulai`     | String       | ✅ Ya    | Format: `YYYY-MM-DD`                                |
  | `tgl_selesai`   | String       | ✅ Ya    | Format: `YYYY-MM-DD`. Harus ≥ `tgl_mulai`          |
  | `keterangan`    | String       | ✅ Ya    | Alasan pengajuan (max 500 karakter)                 |
  | `attachment`    | File         | ⚠️ Lihat | Wajib jika `wajib_lampiran: true`. Mimes: jpg,png,pdf. Max 2MB |

- **Response 201 Created:**
  ```json
  {
    "message": "Pengajuan cuti berhasil dibuat",
    "data": {
      "id": 5,
      "status": "Pending"
    }
  }
  ```
- **Response 422:** Jika validasi gagal (misal tanggal salah, lampiran kurang).

---

### 6.4. Approval Module (Khusus Manager/HR)

**GET `/api/v1/cuti/approvals`** — Daftar Pengajuan Menunggu Persetujuan
- **Akses:** Hanya role `Manajer Departemen`, `HR Manager`, `HR Staff`, `Super Admin`.
- **Response 200 OK:**
  ```json
  {
    "data": [
      {
        "id": 5,
        "nama_karyawan": "Jane Smith",
        "nip": "54321",
        "departemen": "IT",
        "jenis_cuti": "Cuti Tahunan",
        "tgl_mulai": "2026-10-01",
        "tgl_selesai": "2026-10-02",
        "jumlah_hari": 2,
        "alasan": "Urusan keluarga",
        "lampiran": null,
        "status": "Pending",
        "created_at": "2026-09-27 08:00:00"
      }
    ]
  }
  ```
- **Response 403:** `{ "message": "Anda tidak memiliki akses untuk melihat data ini." }`

---

**POST `/api/v1/cuti/approve/{id}`** — Setujui / Tolak Pengajuan
- **Payload (JSON):**
  ```json
  {
    "status": "Approved",
    "catatan": "Disetujui, silakan koordinasi dengan tim."
  }
  ```
  > `status` hanya boleh: `"Approved"` atau `"Rejected"`
- **Response 200 OK:** `{ "message": "Pengajuan berhasil approved" }`
- **Response 403:** Jika bukan approver.
- **Response 404:** Jika ID tidak ditemukan.
- **Response 422:** `{ "message": "Pengajuan ini sudah diproses." }` (jika sudah bukan Pending)

---

*Document prepared for Mobile Engineering Team (Flutter).*
*Last updated: 2026-09-27*
