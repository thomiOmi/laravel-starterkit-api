---
name: laravel-verification
description: Proactive verification loop for Laravel 13 development. Uses MCP tools (database-schema, search-docs) and local tools (Pint, PHPStan, Pest) to ensure high-quality code delivery.
license: MIT
metadata:
  version: "2.0.0"
---

# Laravel Verification Loop

Systematic verification to ensure zero-regression and architectural compliance.

## Verification Checklist

### 1. Database Integrity
- Run `php artisan migrate`.
- **Tool:** Use `database-schema` to verify table columns and constraints match expectations.

### 2. Static Analysis & Linting
- Run `./vendor/bin/pint --format agent`.
- Run `./vendor/bin/phpstan analyse`.
- **Constraint:** Fix all "mixed" type errors; no `@phpstan-ignore`.

### 3. Logic & Behavior
- Run `php artisan test --compact`.
- **Goal:** 100% pass rate.

### 4. Architectural Integrity
- Verify no Model imports between `Modules/A` and `Modules/B`.
- Confirm all new classes are `final`.

## MCP Integration Workflow

Whenever you finish a task, follow this loop:
1. **Explore:** Use `database-schema` to confirm side effects.
2. **Consult:** Use `search-docs` if using a new Laravel 13 feature.
3. **Audit:** Use `read-log-entries` if any test fails with 500 error.
4. **Fix:** Use `pint` and `phpstan` before reporting completion.

## Failure Handling
If a verification step fails:
- Read the specific error message.
- Use `php artisan pail` or `read-log-entries` to get the trace.
- Fix the root cause, then restart the loop from step 1.
