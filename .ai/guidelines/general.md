# Laravel Standard Coding Guidelines

## Tech Stack

| Layer | Tech |
| --- | --- |
| Framework | Laravel 13 + PHP 8.4 |
| Database | MySQL |
| Package manager | Composer 2.9+ |
| Auth | Laravel Sanctum (token-based) |
| Role/Permission | Spatie `laravel-permission` |
| Social Auth | Laravel Socialite |
| Feature Flags | Laravel Pennant |

## API Convention

| Aspek | Detail |
| --- | --- |
| Base URL | `/api/v1/...` (lowercase) |
| Auth | `Authorization: Bearer {token}` (Sanctum) |
| Response | `JsonDataResponse` — `{status, message, data}` (NO `success` boolean) |
| Error | `ProblemResponse` — RFC 7807 |
| Date format | `Y-m-d H:i:s` |
| Route names | `api.v1.{module}.{name}` |

## Testing Rules

- Pest feature tests with `RefreshDatabase` trait
- `beforeEach` seeds `RoleSeeder`, creates admin user, tenant, and cove
- Test each CRUD operation: list, create, view, update, delete, unauthorized access

## Code Quality Rules

- After writing PHP code, run: `./vendor/bin/pint --dirty --format agent`
- Then run: `./vendor/bin/phpstan analyse --memory-limit=512M`
- Fix all errors in code (do NOT modify `phpstan.neon`)
- Do NOT use `@phpstan-ignore` comments — fix the root cause instead
- All datetime fields in API responses **MUST** use `Y-m-d H:i:s` format
- Follow existing code conventions — check sibling files before creating new ones
- Every change must have a corresponding test
