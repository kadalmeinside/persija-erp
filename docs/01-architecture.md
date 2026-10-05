# Arsitektur

## Komponen

- Backend: Laravel 12, PHP 8.2+, MySQL, Sanctum, Spatie Permission, Activitylog.
- Web: Blade dan controller di `app/Http/Controllers`.
- API mobile: route `routes/api.php`, controller API, resource, dan service domain.
- Mobile: Flutter, Riverpod, Dio, camera, geolocator, TFLite, dan `safe_device`.

## Layer backend

`Route -> Middleware -> Controller -> Service -> Model/Database`.

Controller menangani HTTP dan validasi request. Service menangani transaction, business rule, GL posting, approval, saldo, dan attendance. Model hanya mendefinisikan persistence dan relasi.

## Prinsip penting

- Semua operasi finansial harus transactional dan balance sebelum posting.
- Approval mencatat target, actor, level, status, dan timestamp.
- Attendance mobile memakai challenge single-use, foto, lokasi, dan pre-check device.
- Face matching mobile dilakukan on-device; backend tetap menerima foto dan metadata sebagai bukti, bukan sebagai verifikasi biometrik server-side.
