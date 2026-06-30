# Testing

Uses Pest 4 with SQLite in-memory database and `RefreshDatabase` trait.

## Running Tests

```bash
# Full suite
php artisan test --compact

# Single module
./vendor/bin/pest modules/Auth

# Filter by test name
php artisan test --compact --filter=SocialLoginTest
```

## Test Structure

```
tests/
  Feature/
    Shared/CrudTest.php          -- Admin CRUD for users, roles, permissions
    GlobalErrorHandlingTest.php  -- 401, 403, 404, 422, 429 responses
    I18nTest.php                 -- Locale switching (en/id)
    BulkActionTest.php           -- Bulk delete/restore
    Console/MakeModuleTest.php   -- Module generator tests
  Architecture/
    ArchitectureTest.php         -- Pest arch tests (strict types, conventions)

modules/
  Auth/Tests/
    Feature/V1/                  -- Auth flow, device management, social login
    Unit/                        -- Action unit tests (Register, Login, Logout, etc.)
  User/Tests/
    Feature/V1/                  -- User CRUD, filtering
    Unit/Actions/                -- CreateUserAction test
  Role/Tests/
    Feature/V1/                  -- Role filtering, permission CRUD
    Unit/                        -- RoleFactory, PermissionFactory tests
```

## Key Patterns

- `beforeEach` seeds `RoleSeeder` (creates 3 roles + 12 permissions)
- `WithAdminUser` trait provides helper methods (`adminGet`, `adminPost`, etc.)
- Tests use `actingAs()` with specific roles/permissions for authorization tests
- Unit tests mock repositories or use model factories directly
- All tests use `RefreshDatabase` (configured in `tests/Pest.php` and module Pest files)

## Writing Tests

```bash
# Create feature test
php artisan make:test --pest SomeFeatureTest

# Test file goes in the relevant module's Tests/ directory
```
