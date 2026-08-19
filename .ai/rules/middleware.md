---
paths:
  - 'modules/*/app/Http/Middleware/**'
  - 'app/Http/Middleware/**'
---

# Middleware

## Goal

Module-specific middleware in `modules/{Module}/Http/Middleware/`; global middleware in `app/Http/Middleware/`.

## Rules

1. Middleware used only by specific module routes lives in the module
2. Global middleware (auth, throttle, security headers) lives in app
3. Middleware aliases are registered explicitly, not magic discovery
4. Middleware names are verb-first and carry NO `Middleware` suffix (Laravel-style, enforced by the architecture test): `EnsureFeatureIsActive`, `AddTraceId`, `AddSecurityHeaders`, `HandleIdempotentRequests`, `SetLocale`, `AddDeprecationHeaders`. Multi-word aliases are kebab-case (`feature-flag`, `trace-id`); single-word aliases stay bare (`idempotency`, `sunset`, `active`, `verified`).
5. Middleware that only runs globally (appended via `$middleware->append(...)`, e.g. `SetLocale`) needs no alias. Middleware referenced by name in routes must have an explicit alias.
6. Exception responses bypass response-phase middleware (the `$next()` closure never runs on a throw), so the `$exceptions->respond()` block in `bootstrap/app.php` re-applies `AddTraceId` + `AddSecurityHeaders`. This block is REQUIRED — the 404 assertion in `GlobalApiMiddlewareTest` fails without it.
7. `EnsureFeatureIsActive` build-time-first: resolves a flag in two ways, in order. (1) A two-segment name (`{alias}.{feature}`) that exists in registry features (`config()->has("{alias}.features.{name}")`) is a build-time decision read from config, NOT toggleable at runtime via `Feature::activate()`/`deactivate()`; toggle by `Config::set`. (2) Anything else falls back to Pennant `Feature::active` (e.g. class features like `BetaFeature`). Inactive returns `AccessDeniedHttpException` 403 `auth.http_forbidden`.

## Forbidden

- No global middleware inside modules
- No middleware without an alias when referenced by name in routes
- No `Middleware` suffix on middleware class names