# Monitoring: Telescope & Pulse

Proyek ini terintegrasi dengan **Laravel Telescope** untuk debugging teknis dan **Laravel Pulse** untuk monitoring performa.

## Akses Dashboard

Kedua dashboard ini diproteksi dan hanya dapat diakses oleh user dengan role `super-admin`.

- **Telescope:** `/telescope`
- **Pulse:** `/pulse`

## Laravel Telescope

Telescope merekam detail teknis setiap request, query database, exception, email, dan lainnya.
- Di lingkungan **local**, semua aktivitas direkam.
- Di lingkungan **production**, filter diterapkan untuk hanya merekam aktivitas penting (failed requests, exceptions, dll).

## Laravel Pulse

Pulse memberikan ringkasan performa aplikasi secara real-time, termasuk:
- Request paling lambat.
- Query database yang memakan waktu lama.
- Penggunaan CPU dan Memory server.
- Statistik error.

## Keamanan

Proteksi akses dikelola melalui `TelescopeServiceProvider` dan `PulseServiceProvider` menggunakan Gate Laravel:

```php
Gate::define('viewTelescope', function ($user) {
    return $user->hasRole('super-admin');
});
```
