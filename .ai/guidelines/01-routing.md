# Routing & Versioning Standards

This project follows a modular routing structure. All API routes must be versioned and organized within their respective modules.

## 1. Directory Structure

Routes are defined within each module's `Routes` directory. For more details on the modular structure, see [21-modular-architecture.md](21-modular-architecture.md).

```text
modules/
  {ModuleName}/
    Routes/
      v1.php
      v2.php
```

## 2. API Versioning Strategy

- **Mandatory Versioning**: Always version endpoints from the beginning.
- **Explicit Version Coexistence**: Multiple versions (v1, v2) can coexist in the same module.
- **Sunset Deprecation**: Use the `Sunset` header for deprecated versions as detailed in [12-middleware.md](12-middleware.md).

## 3. Route Definition Rules

### Middleware Stack:
Every route group must apply a specific middleware stack:
1. `force.json` (Required first)
2. `auth:sanctum` (If protected)
3. `throttle:api` (Mandatory for all)

### Naming & Precision:
- **Named Routes**: Use resource-relative names (e.g., `index`, `store`).
- **Single-Action Controllers**: Use invokable controllers only (e.g., `StoreController::class`).

## 4. Example Definition

```php
Route::prefix('posts')
    ->middleware(['force.json', 'auth:sanctum', 'throttle:api'])
    ->name('posts.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
        Route::post('/', StoreController::class)->name('store');
    });
```

## 5. Anti-Patterns
- ❌ Do not use resourceful controllers (`Route::resource`).
- ❌ Do not version globally; version per-module/resource.
- ❌ Do not omit `force.json` or `throttle:api` middleware.
- ❌ Do not define API routes in the root `routes/api.php` file.
