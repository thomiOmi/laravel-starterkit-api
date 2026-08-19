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
