# Testing

## Backend

```bash
php artisan test
```

Suite baseline saat dokumentasi ini ditulis: 89 test dan 200 assertion berhasil.

Test penting mencakup approval, cuti, saldo reversal, role access, pengajuan, payroll service, dan budget.

## Database

```bash
php artisan migrate:fresh
php artisan migrate:status
```

`migrate:fresh` hanya untuk lokal/testing dengan data disposable.

## Mobile

```bash
flutter test
flutter analyze
```

Analyzer dapat melaporkan lint/deprecation existing; error compile harus selalu diperbaiki sebelum release.

## Manual End-to-End (E2E) Testing (Web UI)

Untuk menguji workflow sistem secara menyeluruh dari Web UI (terutama fungsionalitas approval dan akses *role*), Anda dapat menggunakan `E2ETestingSeeder`. Seeder ini akan menyuntikkan data dummy yang komprehensif, termasuk struktur departemen, *approval rules*, pengguna (*Direktur*, *Finance*, *HR*, *Staf*), serta berbagai skenario pengajuan (Dana, Cuti, Pinjaman, dan Invoice) yang saling berkaitan.

Jalankan perintah berikut:
```bash
php artisan db:seed --class=E2ETestingSeeder
```

### Akun Pengujian
Gunakan kredensial berikut untuk *login* ke Web UI (password untuk semua akun adalah `password`):

1. **Direktur** (sebagai Approver Tertinggi): `test.direktur@persija.id`
2. **Finance** (sebagai Approver Keuangan): `test.finance@persija.id`
3. **HR** (sebagai Approver Cuti): `test.hr@persija.id`
4. **Staf 1** (Mengajukan Dana Operasional & Pinjaman): `test.staf1@persija.id`
5. **Staf 2** (Mengajukan Cuti Tahunan): `test.staf2@persija.id`

### Skenario Uji (Tertunda / Pending Approval)
Setelah seeder dijalankan, terdapat beberapa skenario pengajuan yang siap untuk di-approve:
- **Pengajuan Dana:** Diajukan oleh `Staf 1`, menunggu approval dari `Direktur` (Lvl 1) & `Finance` (Lvl 2).
- **Cuti Tahunan:** Diajukan oleh `Staf 2`, menunggu approval dari `HR` (Lvl 1) & `Direktur` (Lvl 2).
- **Pinjaman:** Diajukan oleh `Staf 1`, menunggu approval dari `Direktur`.
- **Invoice:** Telah di-*approve* otomatis oleh Finance dan saat ini dalam status `Draft/Pending Approval` (menunggu finalisasi/approval oleh `Direktur`).

Gunakan akun-akun di atas untuk memastikan *dashboard* menampilkan notifikasi dan dokumen yang sesuai dengan wewenang *role* masing-masing.
