# Subscription Management

Sistem manajemen langganan (SaaS) memungkinkan pembatasan fitur aplikasi berdasarkan paket yang dipilih oleh tenant.

## Model Data

1. **SubscriptionPlan (Global):** Daftar paket yang tersedia (Free, Pro, Enterprise). Bersifat global (tidak tenant-aware).
2. **Subscription (Tenant-aware):** Menghubungkan tenant dengan plan tertentu dan menyimpan status masa aktif.

## Pembatasan Fitur

Fitur dapat dibatasi menggunakan middleware `plan.feature`.

### Contoh Penggunaan di Routes:

```php
Route::middleware(['auth:sanctum', 'plan.feature:webhooks'])->group(function () {
    Route::apiResource('webhooks', WebhookController::class);
});
```

Jika tenant tidak memiliki fitur `webhooks` di dalam field `features` pada paket langganannya, sistem akan mengembalikan respon `403 Forbidden` dengan kode error `FEATURE_NOT_IN_PLAN`.

## Mematikan Fitur Secara Global

Untuk mematikan fitur modul tertentu secara total, Anda dapat menghapus atau menonaktifkan rutenya di `modules/{Module}/Routes/v1.php`.
