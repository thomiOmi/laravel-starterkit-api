# RBAC (Role-Based Access Control)

Sistem otorisasi menggunakan paket **Spatie Laravel Permission** yang telah diintegrasikan dengan arsitektur modular.

## 1. Konsep Dasar
- **Roles:** Kumpulan izin akses (misal: `super-admin`, `admin`, `user`).
- **Permissions:** Izin akses spesifik ke fitur tertentu (misal: `user.view`, `webhook.create`).

## 2. Penggunaan di Middleware
Anda dapat membatasi akses route berdasarkan role atau permission langsung di file route modul:

```php
// Berdasarkan Role
Route::get('/admin', [AdminController::class, 'index'])->middleware('role:admin');

// Berdasarkan Permission
Route::post('/webhooks', [WebhookController::class, 'store'])->middleware('permission:webhook.create');
```

## 3. Super Admin
User dengan role `super-admin` memiliki hak akses ke **seluruh fitur** secara otomatis. Logika ini diatur di `App\Providers\AppServiceProvider` menggunakan `Gate::before`.

## 4. Tenant Scoping
Sama seperti data lainnya, data Role dan Permission bersifat **terisolasi per tenant**. Role yang dibuat di Tenant A tidak akan tersedia untuk Tenant B, kecuali didefinisikan sebagai global (central).
