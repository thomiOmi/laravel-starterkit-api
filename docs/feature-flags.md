# Feature Flags (Laravel Pennant)

Proyek ini menggunakan **Laravel Pennant** untuk mengelola fitur secara dinamis. Ini memungkinkan Anda untuk mengaktifkan atau menonaktifkan fitur untuk pengguna atau tenant tertentu tanpa mengubah kode.

## 1. Mendefinisikan Fitur

Fitur didefinisikan di dalam `app/Providers/AppServiceProvider.php` (atau provider khusus jika fitur sudah banyak).

```php
use Laravel\Pennant\Feature;
use Modules\User\Models\User;

public function boot(): void
{
    Feature::define('new-dashboard', function (User $user) {
        return $user->hasRole('admin');
    });
}
```

## 2. Mengecek Fitur

### Di Controller atau Action
```php
use Laravel\Pennant\Feature;

if (Feature::active('new-dashboard')) {
    // Tampilkan dashboard baru
}
```

### Di Middleware (Opsional)
Anda dapat membuat middleware khusus untuk membatasi akses rute berdasarkan fitur.

## 3. Penyimpanan
Secara default, status fitur disimpan di database menggunakan tabel `features`. Hal ini memungkinkan perubahan status fitur secara runtime tanpa deploy ulang.

## 4. Keuntungan Enterprise Ready
- **Beta Testing:** Aktifkan fitur baru hanya untuk sekelompok kecil user.
- **Gradual Rollout:** Luncurkan fitur secara bertahap.
- **Tenant-specific Features:** Berikan fitur eksklusif untuk tenant tertentu.
