# Enum dan Konfigurasi

Konfigurasi runtime berada di `.env` dan `config/*`; secret tidak boleh ditulis di repository.

Konfigurasi minimum:

- koneksi database.
- storage disk dan URL.
- Sanctum/authentication.
- queue dan cache.
- timezone aplikasi.

Status domain seperti payroll, pengajuan, approval, dan attendance harus menggunakan enum atau nilai yang didefinisikan migration/model. Jangan menambah status hanya di UI.
