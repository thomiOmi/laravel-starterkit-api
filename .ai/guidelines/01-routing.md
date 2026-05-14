# Routing Standards

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

## 2. Route Definition Standards

Each module's route file should define a group for its main resource.

### Mandatory Rules:
- **Versioned**: Always define routes within a versioned file (e.g., `v1.php`).
- **Throttled**: The `throttle:api` middleware must be applied to every route group.
- **Named Routes**: Every route must have a name. Since `RouteServiceProvider` already adds a prefix, use a resource-relative name.
- **Single-Action Controllers**: Use invokable controllers instead of resourceful ones.

### Example (`modules/User/Routes/v1.php`):

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\V1\IndexController;
use Modules\User\Controllers\V1\StoreController;
use Modules\User\Controllers\V1\ShowController;
use Modules\User\Controllers\V1\UpdateController;
use Modules\User\Controllers\V1\DestroyController;

Route::prefix('users')->middleware(['auth:sanctum', 'throttle:api'])->name('users.')->group(function () {
    Route::get('/', IndexController::class)->middleware('can:user.view')->name('index');
    Route::post('/', StoreController::class)->middleware('can:user.create')->name('store');
    Route::get('/{user}', ShowController::class)->middleware('can:user.view')->name('show');
    Route::put('/{user}', UpdateController::class)->middleware('can:user.edit')->name('update');
    Route::delete('/{user}', DestroyController::class)->middleware('can:user.delete')->name('destroy');
});
```

## 3. Versioning Strategy

- **Coexistence**: Multiple versions (v1, v2) can coexist in the same module.
- **Explicit Versioning**: Never omit the version prefix.
- **Sunset Header**: When an endpoint is deprecated, use a `sunset` middleware (if implemented) to signal the removal date to clients.

## 4. Anti-Patterns
- ❌ Do not use resourceful controllers (`Route::resource`).
- ❌ Do not define API routes in `routes/api.php` if they belong to a module.
- ❌ Do not omit `throttle:api` middleware.
- ❌ Do not use bare integers for status codes (use Symfony constants in controllers instead).
