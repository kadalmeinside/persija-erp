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
