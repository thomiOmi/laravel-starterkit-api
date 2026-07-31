# Coding Standards

## PHP Requirements
- PHP 8.4+
- `declare(strict_types=1)` on every file
- Use constructor property promotion, `final readonly` classes, typed properties
- No `mixed` types; use specific type hints

## Naming
- Classes: `PascalCase` (e.g. `CreateUserAction`)
- Methods/variables: `camelCase` (e.g. `isRegistered`)
- Database tables: `snake_case` plural (e.g. `users`)
- Route names: `v1.{module}.{name}` (e.g. `v1.auth.register`, `v1.user.index`)

## Architecture
- Controllers: `final readonly` invokable classes with `__invoke()` -- no business logic
- Actions: `final readonly` with `handle()` method -- single responsibility
- Models in `Modules/{Module}/Models/`. Use `HasDefaultBehavior` (ULID, soft deletes, date format)
- No repositories: Use Eloquent directly within actions
- Payloads: `final readonly` DTOs with `fromRequest()` factory and `toArray()`

## API
- All responses: `SuccessResponse` or `ProblemResponse` (RFC 9457) -- `{status, title?, detail?, data, meta?}`
- All errors: `ProblemResponse` (RFC 9457) -- `{type, title, status, detail, timestamp, instance?}`
- Date format: `Y-m-d H:i:s`
- Locale via `Accept-Language` header (en, id)
- No `success` boolean in responses

## Attributes
Use PHP 8 attributes over class properties:
- `#[Fillable([...])]` on models for mass assignment
- `#[Hidden([...])]` on models for hidden fields
- `#[UseFactory(Factory::class)]` on models for factory binding

## Config Access
Use typed config helpers instead of `Config` facade:
- `config()->string('key')`
- `config()->integer('key')`
- `config()->boolean('key')`
- `config()->array('key')`

## Rate Limiting
Three tiers:
- `auth`: 5/min per email + 10/min per IP
- `authenticated`: 120/min
- `api`: 60/min

## Testing
- Pest 5 with `RefreshDatabase`
- `beforeEach` seeds roles (web + sanctum), calls `forgetCachedPermissions()`, creates admin with `loginAsUser()`
- Feature tests for each CRUD + auth flow
- Unit tests for each action class
- Response assertion helpers: `assertSuccessResponse(status)`, `assertProblemResponse(status)`, `assertPaginatedResponse()`
- Every change must have a corresponding test
## Code Quality

- Format: `./vendor/bin/pint --dirty --format agent`
- Static analysis: `./vendor/bin/phpstan analyse --memory-limit=512M` (level max, test files included via `pest-plugin-phpstan`; no baseline, no `@phpstan-ignore`)
- Type coverage: `php artisan test --coverage --type-coverage --min=100 --memory-limit=512M`
  - `--memory-limit=512M` is required: phpunit runs as a child process that ignores `-d memory_limit`; the type-coverage plugin applies it via `ini_set` in-process
- Rector dry run: `composer rector:dry` (`PestSetList::CODING_STYLE`), part of `composer ci:check`
- Clear PHPStan cache with `phpstan clear-result-cache` (prefix `PAO_DISABLE=1` — the `laravel/pao` wrapper breaks non-analyse commands)
- No `dd()`, `dump()`, `console.log()` in committed code
- Production security gate: `php artisan security:check`
