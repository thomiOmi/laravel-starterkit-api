# Repository Caching Strategy

Proyek ini menyediakan mekanisme caching terstandarisasi di level Repository untuk meningkatkan performa API dengan mengurangi query database yang berulang.

## 1. Mekanisme `HasCache`

Caching diimplementasikan melalui trait `App\Traits\Repositories\HasCache` yang digunakan oleh `BaseRepository`. Trait ini menyediakan metode pembantu untuk menyimpan dan mengambil data dari cache secara transparan.

### Fitur Utama:
- **Transparent Caching:** Secara otomatis menyimpan hasil query `findById`.
- **Version-Based Invalidation:** Menggunakan strategi rotasi versi kunci (cache busting) daripada melakukan flush global. Hal ini aman digunakan di lingkungan *shared cache*.
- **Driver Agnostic:** Bekerja dengan semua driver Laravel (Redis, Database, File, Array).

## 2. Penggunaan dalam Repository

Secara default, `BaseRepository` sudah menggunakan trait ini. Untuk melakukan caching pada metode kustom di repository anak, gunakan metode `cache()`:

```php
public function getActiveUsers()
{
    return $this->cache('active_users', function () {
        return $this->model->where('active', true)->get();
    });
}
```

### Invalidation Otomatis
Mekanisme cache akan otomatis "dibersihkan" (melalui peningkatan versi kunci) pada saat operasi berikut dipanggil melalui repository:
- `create()`
- `update()` / `updateOrCreate()`
- `delete()`
- `bulk()`

## 3. Strategi Invalidation (Version Rotation)

Alih-alih menghapus entri cache satu per satu atau melakukan `Cache::flush()`, sistem ini menyimpan nomor versi untuk setiap repository (misalnya `userrepository.version`).

Setiap kunci cache menyertakan nomor versi ini: `userrepository.v1.find.ULID`. Saat data berubah, nomor versi ditingkatkan menjadi `v2`, sehingga semua entri cache lama (`v1`) otomatis terabaikan dan data baru akan diambil dari database.

## 4. Konfigurasi

Pengaturan caching dapat ditemukan di `config/cache_enterprise.php`:

```php
return [
    'enabled' => env('CACHE_ENABLED', true),
    'default_ttl' => env('CACHE_TTL', 3600), // 1 jam
];
```

Untuk menonaktifkan caching secara global (misalnya saat debugging), atur `CACHE_ENABLED=false` di file `.env`.
