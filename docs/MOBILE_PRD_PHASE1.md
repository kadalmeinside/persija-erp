# Product Requirements Document (PRD) - Mobile ESS (Phase 1)
**Project Name:** ERP Enterprise Mobile App
**Platform:** Android & iOS (Native via Flutter)
**Version:** 1.0.0 (Phase 1)

---

## 1. Executive Summary
Aplikasi mobile ini (Employee Self-Service) dirancang secara spesifik untuk memfasilitasi karyawan dalam melakukan presensi harian secara aman dan mengajukan cuti. Tujuan utama pemisahan absensi dari web ke *mobile native* adalah untuk menekan tingkat kecurangan (seperti penggunaan *Fake GPS* atau absen titip) melalui pelacakan lokasi *hardware* perangkat dan pengambilan *selfie* langsung dari kamera HP.

---

## 2. Tech Stack & Architecture
- **Framework:** Flutter (Dart) - *Cross-platform (Android & iOS)*
- **State Management:** Riverpod / BLoC (Disarankan menggunakan arsitektur yang solid untuk *scale-up* di masa depan).
- **HTTP Client:** Dio (Dilengkapi Interceptor untuk menangani *Bearer Token* dan respon `401 Unauthorized`).
- **Local Storage:** `flutter_secure_storage` (Untuk menyimpan Token Sanctum) & `shared_preferences`.
- **Location Service:** `geolocator` & native Android/iOS location services.
- **Camera:** `camera` atau `image_picker` (Dibatasi hanya bisa menggunakan kamera langsung, **TIDAK BOLEH** ambil gambar dari galeri untuk *Clock-In/Out*).
- **Security Check:** `root_tail` / `freerasp` / `trust_fall` (Untuk mendeteksi Root/Jailbreak, Emulator, dan Mock Locations).

---

## 3. UI/UX & Design Guidelines
Aplikasi harus merefleksikan estetika *Dashboard Web ERP* yang sudah kita bangun (Gaya Modern TailwindCSS):
- **Color Palette:** Mengikuti warna primer ERP (Misal: *Primary Red/Blue* khas brand, dengan aksen abu-abu terang untuk *background*).
- **Typography:** Menggunakan *Google Fonts* modern seperti **Inter** atau **Roboto**.
- **Card & Layout:** Menggunakan pendekatan antarmuka berbasis *Card* (*Rounded corners*, bayangan halus/drop shadow ringan) dengan jarak ruang (*padding*) yang lega untuk perangkat sentuh.
- **Micro-interactions:** Berikan *feedback* haptic (getaran kecil) saat menekan tombol absen, dan animasi *loading state* (Shimmer UI) saat mengambil data.

---

## 4. Key Features (Phase 1)

### 4.1. Authentication (Login & Profile)
- **Login Screen:** Input Email dan Password.
- Sistem menggunakan token Sanctum yang di-*return* setelah *login* sukses.
- **Home/Dashboard:** Menampilkan foto profil karyawan, Nama, Jabatan, Departemen, dan **Status Kehadiran Hari Ini** (*Clock-In* / *Clock-Out* timer).

### 4.2. Absensi (Attendance) dengan Geofencing
Tombol utama pada layar Beranda akan berubah dinamis antara "Absen Masuk" dan "Absen Pulang".
- **Geofencing Engine:**
  - Aplikasi memanggil endpoint `/user` atau memeriksa cache untuk mendapatkan konfigurasi `lokasi_kantor` (Latitude, Longitude, Radius meter) & `is_strict_location`.
  - Jika `is_strict_location == true`, aplikasi menghitung jarak HP dengan titik kantor (menggunakan *Haversine formula* atau fungsi bawaan Geolocator).
  - Jika karyawan berada **di luar radius**, tombol Absen **Terkunci/Disabled** (Berubah warna abu-abu dengan pesan "Anda berada di luar radius kantor").
- **Dinas Luar (Out of Office):** 
  - Karyawan bisa mencentang "Sedang Dinas Luar" jika *meeting* di luar. Saat dicentang, kunci *Geofencing* dilepas, namun *form input catatan* menjadi **WAJIB** diisi.
- **Camera Validation:**
  - Saat absen ditekan, layar kamera terbuka (menghadap depan secara *default*). Pengguna harus memotret dirinya secara *real-time*.

### 4.3. Pengajuan Cuti (Leave Requests)
- **Menu Saldo Cuti:** Menampilkan visualisasi (Gauge Chart / Progress Bar) sisa kuota cuti tahunan, cuti besar, dll.
- **Form Pengajuan Cuti:**
  - *Dropdown* dinamis Jenis Cuti (Diambil dari API).
  - *Date picker* rentang waktu (Tanggal Mulai s/d Tanggal Selesai).
  - *Textarea* alasan.
  - Opsi mengunggah file (PDF/Gambar) dari Galeri/Filesystem jika `wajib_lampiran == true` (Misal: Surat Dokter untuk cuti sakit).
- **Riwayat Cuti:** *List view* histori pengajuan yang sudah ada dengan *badge status* (Pending, Approved, Rejected).

---

## 5. Security & Anti-Fraud Specifications (CRITICAL)
Karena tujuan utama aplikasi native ini adalah **menggagalkan kecurangan**, terapkan filter keamanan ini secara ketat di *level native code (Kotlin/Swift)* maupun melalui library Flutter:

1. **Anti-Mock Location (Fake GPS):**
   - Saat menarik lokasi (GPS), cek atribut perangkat.
   - *Android:* Periksa variabel `isFromMockProvider()` dari objek Location. Jika `true`, tolak akses absen dan lempar peringatan keras.
   - *iOS:* Sulit di-mock tanpa Jailbreak (selain via XCode debug), namun blokir aplikasi jika berjalan di mode debug.
2. **Root & Jailbreak Detection:**
   - Aplikasi tidak boleh dijalankan di perangkat Android yang di-*root* atau iPhone yang di-*jailbreak*. Gunakan *library* seperti `freerasp` atau `flutter_jailbreak_detection`.
3. **Emulator Blocking:**
   - Deteksi apakah *environment* yang berjalan adalah emulator (Bluestacks, Nox, Android Studio Emulator, iOS Simulator). Tolak proses *login/clock-in* jika terdeteksi emulator.
4. **No Gallery for Absensi:**
   - Secara fungsional blokir *Image Picker* menuju galeri saat proses presensi. Aplikasi murni harus berinteraksi dengan API Kamera, tidak melayani file statis dari memori HP.

---

## 6. API Structure Reference (V1)
Semua koneksi ke backend harus menggunakan `https` (Wajib SSL) dengan awalan `/api/v1/...`
Struktur lengkap *request* dan *response* JSON dapat dilihat pada modul dokumentasi admin di: `Admin Dashboard > System Settings > API Documentation`.

**Ringkasan Endpoint:**
- `POST /api/v1/login` - Mendapatkan Token
- `POST /api/v1/logout` - Menghanguskan Token
- `GET /api/v1/user` - Mengambil Profil & Metadata Geofencing
- `GET /api/v1/absensi/today` - Cek jam masuk/pulang hari ini
- `POST /api/v1/absensi/clock-in` - Eksekusi absen masuk (Kirim koordinat & foto via Multipart)
- `POST /api/v1/absensi/clock-out` - Eksekusi absen pulang
- `GET /api/v1/absensi/history` - Histori absen
- `GET /api/v1/cuti/jenis` - Dropdown Jenis Cuti
- `GET /api/v1/cuti/balances` - Saldo cuti berjalan
- `GET /api/v1/cuti/requests` - Histori pengajuan
- `POST /api/v1/cuti/request` - Submit pengajuan baru (Multipart)

---
*Document prepared for Mobile Engineering Team (Flutter).*
