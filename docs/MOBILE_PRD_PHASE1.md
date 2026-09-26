# Product Requirements Document (PRD) - Mobile ESS (Phase 1)
**Project Name:** Persija ERP Enterprise Mobile App
**Platform:** Android & iOS (Native via Flutter)
**Version:** 1.0.0 (Phase 1)

---

## 1. Executive Summary
Aplikasi mobile ini (Employee Self-Service) dirancang secara spesifik untuk memfasilitasi karyawan dalam melakukan presensi harian secara aman dan mengajukan cuti. Tujuan utama pemisahan absensi dari web ke *mobile native* adalah untuk menekan tingkat kecurangan (seperti penggunaan *Fake GPS* atau absen titip) melalui pelacakan lokasi *hardware* perangkat dan pengambilan *selfie* langsung dari kamera HP. Desain antarmuka (UI) akan secara konsisten mengikuti arsitektur visual versi Web Persija ERP.

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

## 3. Detail UI/UX & Desain Antarmuka
Aplikasi wajib menggunakan palet warna utama (Identitas Persija) dan *layout* bergaya modern *enterprise*. Desain harus identik dengan rasa (feel) versi Web Tailwind CSS.

### 3.1. Color Palette & Typography
- **Primary Color:** Merah Persija (`#D2122E` atau `#DC2626` Tailwind Red-600) untuk *Primary Buttons* dan *Active States*.
- **Secondary Color:** Abu-abu gelap (`#1F2937` Tailwind Gray-800) untuk Teks Utama & Header.
- **Background Color:** Abu-abu sangat terang (`#F3F4F6` Tailwind Gray-100) untuk *background* aplikasi, putih (`#FFFFFF`) untuk *Cards* (Container).
- **Success Color:** Hijau (`#10B981` Tailwind Emerald-500) untuk notifikasi sukses dan tombol "Absen Masuk".
- **Danger Color:** Merah pekat (`#EF4444` Tailwind Red-500) untuk notifikasi gagal dan tombol "Absen Pulang".
- **Typography:** **Inter** atau **Roboto**. Judul (Font-weight: 700/Bold), Subteks (Font-weight: 400/Regular).
- **Radius & Shadow:** Gunakan sudut membulat (*Rounded* 8px - 12px) dan *box-shadow* tipis (Drop shadow SM/MD) pada semua elemen *Card* dan *Button*.

### 3.2. Struktur Navigasi (Hamburger Menu & App Bar)
- **App Bar (Top Bar):** 
  - Kiri: **Ikon Hamburger** (Garis tiga) untuk membuka *Side Drawer*.
  - Tengah: Teks Judul Halaman (Misal: "Dashboard", "Kehadiran").
  - Kanan: **Ikon Lonceng** (Notifikasi) dan **Avatar Bulat** foto profil karyawan. Background App Bar putih pekat dengan *border-bottom* tipis abu-abu.
- **Side Drawer (Menu Hamburger):**
  - Akan muncul meluncur dari kiri ketika ikon Hamburger ditekan.
  - **Header Menu:** Background Merah Persija dengan logo klub/perusahaan, dan detail Profil (Nama Karyawan, NIP, Jabatan).
  - **Daftar Menu (List Tile):**
    1. **Dashboard** (Ikon Home) - *Aktif secara default*.
    2. **Absensi** (Ikon Map Pin) - Menu presensi & histori kehadiran.
    3. **Cuti** (Ikon Calendar) - Menu pengajuan & sisa kuota cuti.
    4. **Keluar** (Ikon Log Out, berwarna merah di area paling bawah).

---

## 4. Detail Fitur & Spesifikasi Halaman (Phase 1)

### 4.1. Halaman Login (Login Screen)
- **Posisi:** Berada di tengah layar (*Center-aligned*).
- **Visual:** Logo perusahaan di bagian atas, diikuti dengan *Text Field* bergaris luar halus (*outlined*) untuk Email dan Password.
- **Tombol Utama:** Tombol lebar (100% width) bertuliskan "Masuk", warna latar Merah (`#DC2626`), tulisan putih tebal. 
- *Loading State:* Tombol berubah menjadi *spinner* melingkar saat proses login berlangsung.

### 4.2. Halaman Dashboard (Home)
- **Kartu Profil:** Berada di paling atas, berwarna putih. Menampilkan "Selamat Datang, [Nama]", Jabatan, dan Departemen.
- **Status Kehadiran Hari Ini (Card Absensi):**
  - Letak tepat di bawah kartu profil.
  - Menampilkan dua waktu: **Jam Masuk** (Kiri) dan **Jam Pulang** (Kanan) dengan ukuran *font* digital/tebal.
  - **Tombol Dinamis:** Jika belum absen masuk, akan ada tombol Hijau penuh ("Absen Masuk"). Jika sudah masuk, tombol berubah menjadi Merah penuh ("Absen Pulang"). Posisinya memanjang *full width* di dalam *Card*.
- **Quick Action (Grid):** Barisan *icon button* di bagian tengah bawah untuk akses cepat: "Ajukan Cuti", "Histori Absen", dsb.

### 4.3. Fitur Absensi (Geofencing & Selfie)
- Saat menekan "Absen Masuk", sistem memproses 3 layar / *step*:
  1. **Layar Cek Lokasi (Map/Loading):** Memastikan GPS hidup. Titik lokasi pengguna saat ini (*Blue dot*) akan dicek silang dengan *Circle Geofence* lokasi kantor.
     - *Validasi:* Jika di luar radius -> Muncul *Snackbar* / *Alert* Peringatan (Tolak Absen).
     - *Opsi Dinas Luar:* Tersedia *Checkbox* di atas tombol Lanjut: `[ ] Sedang Dinas Luar`. Jika dicentang, akan muncul *Text Field* tambahan "Catatan/Lokasi Dinas", dan blokir radius dimatikan.
  2. **Layar Kamera (Selfie):** Kamera depan terbuka berbingkai bulat (*circle crop* frame) di tengah. Tombol foto membulat di bagian bawah tengah layar. Terdapat instruksi: "Pastikan wajah terlihat jelas". **Galeri dilarang keras**.
  3. **Layar Konfirmasi & Upload:** Menampilkan hasil foto dan koordinat. Tombol "Kirim Absensi" berwarna Merah Persija.
  
### 4.4. Halaman Pengajuan Cuti
- **Header Saldo (Gauge/Card):** Tiga kartu kecil horizontal atau satu kartu lebar menampilkan informasi: "Cuti Tahunan", "Kuota Default: 12", "Sisa: 10". Warna biru/ungu pudar untuk latar.
- **Form Pengajuan:** 
  - Seluruh *field* berbentuk *outlined box*.
  - *Dropdown* "Jenis Cuti" (Misal: Cuti Tahunan, Sakit).
  - Kolom Tanggal (menggunakan pop-up kalender/`showDatePicker`).
  - *Textarea* panjang untuk "Alasan".
  - Area Kotak Putus-putus (*Dashed Box*) untuk **Upload Bukti/Lampiran**. Di area ini, user bisa memanggil `image_picker` (Boleh kamera / galeri).
- **Tombol Aksi:** Tombol biru/merah "Kirim Pengajuan" (*full width*) di bagian bawah layar.

### 4.5. Halaman Histori (Absen & Cuti)
- Menggunakan *List View* (urutan terbaru di atas).
- Masing-masing item histori ditampilkan dalam *Card* putih tipis.
- *Status Badge:* 
  - Hijau ("Hadir", "Disetujui")
  - Merah ("Ditolak", "Alpa")
  - Kuning ("Pending").

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
Setiap *request* yang membutuhkan autentikasi harus mengirimkan header:
`Authorization: Bearer {token}`
`Accept: application/json`

### 6.1. Authentication (Login & Profile)

**1. POST `/api/v1/login`**
- **Payload (JSON):**
  ```json
  {
    "email": "user@persija.id",
    "password": "password123"
  }
  ```
- **Response Sukses (200 OK):** Mengembalikan token Sanctum.
  ```json
  {
    "data": {
      "token": "1|xyz123...",
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@persija.id",
        "role": "Staf"
      }
    }
  }
  ```

**2. GET `/api/v1/user`**
- **Tujuan:** Mendapatkan metadata profil dan parameter *Geofencing* untuk mengunci tombol absen.
- **Response Sukses (200 OK):**
  ```json
  {
    "data": {
      "id": 1,
      "name": "John Doe",
      "karyawan": {
        "nip": "12345",
        "nama_lengkap": "John Doe",
        "jabatan": "Staff IT",
        "departemen": "IT",
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

### 6.2. Absensi (Attendance) Module

**1. GET `/api/v1/absensi/today`**
- **Tujuan:** Mengecek status absen hari ini untuk merubah warna/status tombol di *Dashboard*.
- **Response Sukses (200 OK):**
  ```json
  {
    "data": {
      "clock_in": "08:00:00",
      "clock_out": null,
      "status": "Hadir"
    }
  }
  ```

**2. POST `/api/v1/absensi/clock-in` & `/api/v1/absensi/clock-out`**
- **Header Khusus:** `Content-Type: multipart/form-data`
- **Payload (Form Data):**
  - `latitude` (Double/Numeric) - Wajib
  - `longitude` (Double/Numeric) - Wajib
  - `photo` (File/Image) - Opsional (Sesuai kebijakan)
  - `is_dinas_luar` (Boolean/Int `1` atau `0`) - Opsional (Jika dicentang)
  - `catatan` (String) - Opsional (Wajib jika dinas luar)
- **Response Sukses (201 Created):**
  ```json
  {
    "message": "Berhasil absen masuk.",
    "data": {
      "id": 10,
      "clock_in": "08:00:00"
    }
  }
  ```
- **Response Gagal (422 Unprocessable Entity):** (Misal di luar radius atau *fake* GPS jika divalidasi backend).

**3. GET `/api/v1/absensi/history?month=09&year=2026`**
- **Tujuan:** Mendapatkan riwayat absen bulanan.
- **Response Sukses (200 OK):**
  ```json
  {
    "data": [
      {
        "id": 10,
        "date": "2026-09-25",
        "clock_in": "08:00:00",
        "clock_out": "17:00:00",
        "status": "Hadir"
      }
    ]
  }
  ```

### 6.3. Cuti (Leave) Module

**1. GET `/api/v1/cuti/jenis`**
- **Tujuan:** Data untuk mengisi *dropdown* pilihan form cuti.
- **Response Sukses (200 OK):**
  ```json
  {
    "data": [
      {
        "id": 1,
        "nama_cuti": "Cuti Tahunan",
        "kuota_default": 12,
        "wajib_lampiran": false
      }
    ]
  }
  ```

**2. GET `/api/v1/cuti/balances`**
- **Tujuan:** Menampilkan sisa cuti berjalan.
- **Response Sukses (200 OK):**
  ```json
  {
    "data": [
      {
        "id": 1,
        "jenis_cuti_id": 2,
        "jenis_cuti": "Cuti Tahunan",
        "saldo_awal": 12,
        "saldo_terpakai": 2,
        "saldo_akhir": 10
      }
    ]
  }
  ```

**3. POST `/api/v1/cuti/request`**
- **Header Khusus:** `Content-Type: multipart/form-data`
- **Payload (Form Data):**
  - `jenis_cuti_id` (Int) - Wajib
  - `tgl_mulai` (String format `YYYY-MM-DD`) - Wajib
  - `tgl_selesai` (String format `YYYY-MM-DD`) - Wajib
  - `keterangan` (String) - Wajib
  - `attachment` (File PDF/Img) - Opsional (Wajib jika tipe cutinya `wajib_lampiran: true`)
- **Response Sukses (201 Created):**
  ```json
  {
    "message": "Pengajuan cuti berhasil dibuat",
    "data": {
      "id": 5,
      "status": "Pending"
    }
  }
  ```

---
*Document prepared for Mobile Engineering Team (Flutter).*
