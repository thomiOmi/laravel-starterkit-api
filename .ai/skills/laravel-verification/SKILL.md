---
name: laravel-verification
description: "Verification loop for code quality."
metadata:
  version: "1.2.0"
---

# Laravel Verification

Mandatory quality checks.

## Sequence
1. `./vendor/bin/pint --format agent`
2. `./vendor/bin/phpstan analyse`
3. `php artisan test`

## Rules
- No static analysis ignores.
- Architecture tests (Pest Arch) MUST pass.
- See `references/static-analysis.md` for common PHPStan fixes.
