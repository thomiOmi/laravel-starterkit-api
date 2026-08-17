---
paths:
  - 'modules/*/routes/**'
  - 'modules/*/app/Providers/RouteServiceProvider.php'
---

# Routes

## Goal

Module route files in `modules/{Module}/routes/` (e.g. `V1.php`), loaded by the module's own `RouteServiceProvider` (`modules/{Module}/app/Providers/RouteServiceProvider.php`) while the module is active. The module provider extends `Illuminate\Foundation\Support\Providers\RouteServiceProvider`, iterates `apiroute.supported_versions` (default `['V1']`), guards each file with `file_exists`, and mounts it on `api/{version}` with route name `{version}.{alias}.`. Routes live in the module; shared/global middleware belongs to the app.

## Rules

1. Base prefix `api/v1/{path}` (no module segment in the URL); route name `v1.{module}.{name}` (e.g. `v1.iam.register`)
2. Keep middleware explicit on the route group (auth:sanctum, throttle, permission, feature.flag) - no hidden middleware in service providers
3. Route files only load if the module is active (FileActivator)
4. Versioned files follow `V{number}.php` casing (V1.php, V2.php); new scaffold modules use `routes/api.php` (see console-commands rule)

## Forbidden

- No route registration outside the module's `routes/` directory
- No hidden middleware in providers

## Example

Route files in `modules/{Module}/routes/V1.php` are relative: the module's `RouteServiceProvider` wraps them with `prefix('api/{version}')`, `middleware(['api'])`, and `name('{version}.{alias}.')`. Add module-specific middleware (auth, throttle, feature.flag) on the route group or route inside the file, then the base prefix completes the URL.

```php
Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('/register', RegisterController::class)
        ->middleware('feature.flag:iam.self-registration')
        ->name('register');
});
```

Final URL `/api/v1/auth/register`, final name `v1.iam.auth.register`.