# Verification & Quality Assurance

This document outlines the mandatory quality checks for the Standard 2026 project.

## 1. Code Formatting (Laravel Pint)
- **Command**: `./vendor/bin/pint --format agent`
- **Constraint**: The `concat_space` rule is set to `none`. Example: `'Hello'.$name` (correct) vs `'Hello' . $name` (incorrect).

## 2. Static Analysis (PHPStan Level 9)
- **Command**: `./vendor/bin/phpstan analyse --memory-limit=512M`
- **Zero Ignores**: `@phpstan-ignore` is strictly prohibited. Fix the root cause.
- **Common Fixes**:
    - **Bulk Operations**: In transactions, use `/** @var int $deletedCount */` to satisfy return type requirements.
    - **JSON Data**: When using `getData(true)` on a response, always wrap it in `is_array()` before accessing keys to avoid `nonOffsetAccessible` errors.

## 3. Automated Testing (Pest)
- **Pest PHP**: The project uses Pest for all tests.
- **Coverage**: Every new feature or bug fix MUST have a corresponding test.
- **Factories**: Use Factories and custom states for all model creation in tests.
- **CRUD Checklist**: List, Create, View, Update, Delete, and Unauthorized access must be tested.

## 4. Architecture Testing (Pest Arch)
Mandatory checks to prevent architectural regression:
- Controllers must NOT access Models directly (must use Actions).
- Controllers and Actions must be `final readonly`.
- No usage of `env()` outside of configuration files.

## 5. Pre-Commit Checklist
Run these commands in order before submitting code:
1. `composer validate`
2. `./vendor/bin/pint --format agent`
3. `./vendor/bin/phpstan analyse --memory-limit=512M`
4. `php artisan test --compact`

## 6. CI/CD Standards
- **Least Privilege**: GitHub workflows must use restricted `permissions`.
- **Caching**: Mandatory use of `actions/cache` for Composer and NPM to speed up pipelines.
- **Concurrency**: Use `concurrency` groups with `cancel-in-progress: true`.
