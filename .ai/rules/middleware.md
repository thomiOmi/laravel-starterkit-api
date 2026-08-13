---
paths:
  - 'modules/*/Http/Middleware/**'
  - 'modules/*/Middleware/**'
  - 'app/Http/Middleware/**'
---

# Middleware

## Goal

Module-specific middleware in `modules/{Module}/Http/Middleware/`; global middleware in `app/Http/Middleware/`.

## Rules

1. Middleware used only by specific module routes lives in the module
2. Global middleware (auth, throttle, security headers) lives in app
3. Middleware aliases are registered explicitly, not magic discovery
4. FeatureFlagMiddleware build-time-first: resolves a flag in two ways, in order. (1) A two-segment name (`{alias}.{feature}`) that exists in registry features (`config()->has("{alias}.features.{name}")`) is a build-time decision read from config, NOT toggleable at runtime via `Feature::activate()`/`deactivate()`; toggle by `Config::set`. (2) Anything else falls back to Pennant `Feature::active` (e.g. `beta-feature` in AppServiceProvider, or module feature classes). Inactive returns `AccessDeniedHttpException` 403 `auth.http_forbidden`.

## Forbidden

- No global middleware inside modules
- No middleware without an alias
