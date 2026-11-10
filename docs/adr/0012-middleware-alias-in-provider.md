# ADR-0012: Middleware Aliases Registered in Module Service Provider

- Status: Accepted
- Date: 2026-08-08

## Context

Module middleware (e.g. `EnsureUserIsActive` in `Modules\IAM\Http\Middleware`) must be referenced from routes. Registering the alias in `bootstrap/app.php` would require importing a module class there, violating module isolation.

## Decision

Middleware aliases are registered via `Route::aliasMiddleware()` inside the module's service provider (`registerMiddlewareAliases()` in `IAMServiceProvider`).

## Consequences

- `bootstrap/app.php` stays free of module imports.
- Route files reference short aliases (`active`, `feature-flag`, `idempotency`) while the binding stays explicit and visible in the provider.
- New modules follow the same pattern for their own middleware.

## Corrections

- 2026-11-10: A middleware used by more than one module no longer lives in a module. `EnsureUserIsActive` moved to `app/Http/Middleware/EnsureUserIsActive.php:20` and its alias `active` is registered in `bootstrap/app.php:45`, which keeps the decision's goal intact: `bootstrap/app.php` imports only `App\...` classes, never a module class. Module-owned middleware with a single consumer still registers its alias in the module service provider. (Original text above is kept as written.)
