# Module Generator

Proyek ini menyediakan command khusus untuk mempercepat pengembangan modul baru dengan struktur yang terstandarisasi.

## Penggunaan Dasar

Untuk membuat modul baru, jalankan:

```bash
php artisan make:module {ModuleName}
```

Command ini akan membuat struktur folder berikut di dalam `modules/{ModuleName}`:
- `Actions`
- `Controllers/V1`
- `Database/Migrations`
- `DTOs`
- `Models`
- `Providers`
- `Repositories`
- `Requests`
- `Resources`
- `Routes`
- `Tests`

## Opsi Tambahan

Anda dapat menambahkan komponen spesifik secara otomatis menggunakan opsi `--include`:

```bash
php artisan make:module Product --include=dto,action,repository,request,resource
```

Atau jalankan tanpa opsi untuk masuk ke **mode interaktif** yang akan menanyakan satu per satu komponen yang ingin dibuat.

## Pendaftaran Modul Otomatis

Modul yang baru dibuat akan secara otomatis terdeteksi jika `ModuleNameServiceProvider` sudah ada di folder `Providers`. Sistem akan secara otomatis memuat:
1. **Migrations:** Melalui `$this->loadMigrationsFrom()` di Service Provider.
2. **Routes:** Melalui `RouteServiceProvider` global yang mencari file `v1.php` di dalam folder `Routes` modul.
