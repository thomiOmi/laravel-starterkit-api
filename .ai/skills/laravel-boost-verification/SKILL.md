---
name: laravel-boost-verification
description: "Workflow for linting, testing, and static analysis."
metadata:
  version: "1.0.0"
  triggers: "Pint, PHPStan, Pest, Architecture Test"
---

# Laravel Boost Verification

The standard workflow to ensure code quality before delivery.

## Workflow
1. **Linting**: Run `./vendor/bin/pint --format agent`.
2. **Static Analysis**: Run `./vendor/bin/phpstan analyse --memory-limit=512M`.
3. **Testing**: Run `php artisan test --compact`.
4. **Arch Testing**: Ensure no direct Model access from Controllers via Pest Arch.

## Rules
- NO `@phpstan-ignore` comments.
- Fix root causes for all static analysis errors.
- Every change MUST have a corresponding test.
