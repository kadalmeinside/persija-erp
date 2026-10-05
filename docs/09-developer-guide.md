# Panduan Developer

## Backend setup

1. Install dependency Composer.
2. Salin `.env.example` menjadi `.env`.
3. Atur database lokal.
4. Jalankan `php artisan key:generate`.
5. Jalankan migration dan seeder sesuai kebutuhan.
6. Jalankan test sebelum perubahan digabungkan.

## Aturan perubahan

- Baca model, migration, policy, service, route, dan test terkait sebelum mengubah schema.
- Untuk baseline lokal, perubahan schema dilakukan pada migration create-table.
- Jangan melakukan `migrate:fresh` terhadap production.
- Perubahan API harus memperbarui `10-mobile-api.md` dan mobile client.
- Jangan mencatat descriptor wajah, nonce, atau credential ke log.
- Transaksi finansial harus memiliki source reference dan dapat diaudit.
