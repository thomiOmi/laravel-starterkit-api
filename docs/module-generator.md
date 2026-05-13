# Module Generator

Proyek ini menyediakan command khusus untuk mempercepat pengembangan modul baru dengan struktur yang terstandarisasi.

## Penggunaan Dasar

Untuk membuat modul baru, jalankan:

```bash
php artisan make:module {ModuleName}
```

Command ini akan membuat struktur folder dan file boilerplate berikut di dalam `modules/{ModuleName}`:
- **Actions:** Terpisah untuk Create, Update, dan Delete.
- **Controllers/V1:** Menggunakan dependency injection untuk Action dan Repository.
- **DTOs:** Untuk transfer data antar layer yang aman (Strict Typing).
- **Repositories:** Berbasis Generics yang sudah terintegrasi dengan `HasCache`.
- **Filters:** Untuk penanganan query string (search, sort, filter) yang terpusat.
- **Tests/Feature:** Template pengetesan CRUD standar yang siap dijalankan.
- **Database:** Migrations, Factories, dan Seeders.

## Mode Interaktif

Secara default, generator berjalan dalam **mode interaktif**. Anda akan ditanya komponen apa saja yang ingin dibuat. Jika ingin menimpa modul yang sudah ada, gunakan opsi `--force`:

```bash
php artisan make:module Product --force
```

## Pendaftaran Modul Otomatis

Modul yang baru dibuat akan secara otomatis terdeteksi jika `ModuleNameServiceProvider` sudah ada di folder `Providers`. Sistem akan secara otomatis memuat:
1. **Migrations:** Melalui `$this->loadMigrationsFrom()` di Service Provider.
2. **Routes:** Melalui `RouteServiceProvider` global yang mencari file `v1.php` di dalam folder `Routes` modul.
