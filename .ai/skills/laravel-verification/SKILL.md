---
name: laravel-verification
description: "Comprehensive verification loop including linting, static analysis (Level 9), and Pest architecture testing."
metadata:
  version: "1.1.0"
  triggers: "Verification, Pint, PHPStan, Pest, Architecture Test, Coverage"
---

# Laravel Verification (Quality First)

This skill defines the mandatory verification pipeline that must be executed and passed before any code is considered "complete".

## 1. Code Formatting (Laravel Pint)
Run Pint with the project-specific agent format:
```bash
./vendor/bin/pint --format agent
```
- **Constraint**: `concat_space` rule requires NO spaces around the concatenation operator (`'a'.$b`).

## 2. Static Analysis (PHPStan)
Maintain Level 9 compatibility. Do NOT use `@phpstan-ignore`.
```bash
./vendor/bin/phpstan analyse --memory-limit=512M
```
- **Bulk Operations**: Document count variables with type hints in transactions (e.g., `/** @var int $deletedCount */`).
- **JSON Handling**: Explicitly verify that `getData(true)` returns an `is_array()` before accessing keys.

## 3. Automated Testing (Pest)
Every change must have a corresponding test.
- **Feature Tests**: Test CRUD operations, unauthorized access, and edge cases.
- **Factories**: Use Factories for state management.
- **Refresh Database**: Use the `RefreshDatabase` trait for stateful tests.

```bash
php artisan test --compact
```

## 4. Architecture Testing (Pest Arch)
Mandatory to enforce structural integrity:
- **No direct Model access** from Controllers.
- **Final & Readonly** enforced for Controllers and Actions.
- **No `env()` usage** outside of config files.

## 5. Security & Deployment
- **Audit**: Run `composer audit` to check for vulnerable dependencies.
- **Optimization**: Run `php artisan optimize:clear` and cache commands in staging/prod.
- **CI/CD**: Ensure GitHub workflows have 'least privilege' permissions and use dependency caching.

## 6. Pre-Commit Verification Sequence
Execute these commands in order:
1. `composer validate`
2. `./vendor/bin/pint --format agent`
3. `./vendor/bin/phpstan analyse`
4. `php artisan test --compact`

If any step fails, the code is NOT ready for submission.
