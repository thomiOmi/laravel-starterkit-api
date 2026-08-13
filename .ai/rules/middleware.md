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

## Forbidden

- No global middleware inside modules
- No middleware without an alias
