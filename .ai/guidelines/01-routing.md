# Routing & Versioning Standards

This project follows a modular routing structure. All API routes must be versioned and organized within their respective modules.

## 1. Directory Structure

Routes are defined within each module's `Routes` directory:

```text
modules/
  {ModuleName}/
    Routes/
      v1.php
      v2.php
```

The `App\Providers\RouteServiceProvider` automatically loads these files and applies the following:
- **URL Prefix**: `api/{version}/` (e.g., `api/v1/`)
- **Route Name Prefix**: `api.{version}.{module_name}.` (e.g., `api.v1.user.`)
- **Middleware**: `api` group

## 2. API Versioning

- **Version from Day One**: Always version endpoints from the beginning.
- **Explicit Versioning**: Multiple versions (v1, v2) can coexist in the same module.
- **Sunset Strategy**: When a versioned endpoint is scheduled for removal, signal this to consumers using the `Sunset` HTTP header.

### Coexistence Example:
```php
// modules/Post/Routes/v1.php
Route::prefix('posts')
    ->middleware(['force.json', 'auth:sanctum', 'throttle:api', 'sunset:2026-12-31'])
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
    });

// modules/Post/Routes/v2.php
Route::prefix('posts')
    ->middleware(['force.json', 'auth:sanctum', 'throttle:api'])
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
    });
```

## 3. Route Definition Standards

### Mandatory Rules:
- **Throttled**: The `throttle:api` middleware must be applied to every route group.
- **Force JSON**: The `force.json` middleware must be the first in the stack.
- **Named Routes**: Use resource-relative names (e.g., `index`, `store`).
- **Invokable Controllers**: Use single-action controllers.

## 4. Anti-Patterns
- ❌ Do not use resourceful controllers (`Route::resource`).
- ❌ Do not omit `force.json` or `throttle:api` middleware.
- ❌ Do not use bare integers for status codes in controllers.
- ❌ Do not version globally; version per-module/resource.
