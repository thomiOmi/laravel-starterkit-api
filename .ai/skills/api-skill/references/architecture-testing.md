# Architecture Testing (Pest Arch)

Architecture Testing wajib digunakan untuk menjaga integritas standar 2026 secara otomatis.

---

## 1. Global Rules

Setiap pengembang wajib memastikan aturan ini lewat di `tests/Feature/ArchitectureTest.php`.

```php
arch('strict types must be used')
    ->expect(['App', 'Modules'])
    ->toUseStrictTypes();

arch('avoid debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
```

## 2. Structural Rules

### Controllers
- Harus `final` dan `readonly`.
- Dilarang mengakses Model atau DB secara langsung (wajib lewat Action).
- Gunakan PHP Attributes untuk dokumentasi.

```php
arch('controllers must be final and readonly')
    ->expect('Modules\*\Controllers')
    ->toBeFinal()
    ->toBeReadonly();

arch('controllers must not access models directly')
    ->expect('Modules\*\Controllers')
    ->not->toUse('Modules\*\Models');
```

### Actions
- Harus `final` dan `readonly`.
- Menjadi satu-satunya tempat untuk logika bisnis dan transaksi DB.

```php
arch('actions must be final and readonly')
    ->expect('Modules\*\Actions')
    ->toBeFinal()
    ->toBeReadonly();
```

### Payloads
- Harus `final` dan `readonly`.
- Tempat utama penggunaan PHP 8.4 Property Hooks.

```php
arch('payloads must be final and readonly')
    ->expect('Modules\*\Payloads')
    ->toBeFinal()
    ->toBeReadonly();
```

## 3. Modular Boundaries

Modul harus bersifat mandiri untuk operasi perubahan state.

```php
arch('modules must not use actions from other modules')
    ->expect('Modules\ModuleA\Actions')
    ->not->toUse('Modules\ModuleB\Actions');
```

*Catatan: Akses Model antar modul diperbolehkan hanya untuk pembacaan data (Read-only).*
