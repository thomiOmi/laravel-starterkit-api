---
paths:
  - 'modules/*/Routes/**'
---

# Routes

## Goal

Module route files in `modules/{Module}/Routes/V1.php`, loaded by the base `ModuleServiceProvider` while the module is active (replaces central discovery in RouteServiceProvider). Routes live in the module; shared/global middleware belongs to the app.

## Rules

1. Base prefix `api/v1/{module}`; route name `v1.{module}.{name}` (e.g. `v1.iam.register`)
2. Keep middleware explicit on the route group (auth:sanctum, throttle, permission, feature.flag) - no hidden middleware in service providers
3. Route files only load if the module is active

## Forbidden

- No route registration outside `Routes/`
- No hidden middleware in providers

## Example

```php
Route::middleware(['auth:sanctum', 'throttle:api'])
    ->prefix('api/v1/iam')
    ->name('v1.iam.')
    ->group(function (): void {
        Route::post('/register', RegisterController::class)->name('register');
    });
```
