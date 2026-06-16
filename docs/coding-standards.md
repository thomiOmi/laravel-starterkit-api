# Coding Standards

## PHP Requirements
- PHP 8.4+
- `declare(strict_types=1)` on every file
- Use constructor property promotion, readonly classes, typed properties
- No `mixed` types; use specific type hints

## Naming
- Classes: `PascalCase` (e.g. `CreateUserAction`)
- Methods/variables: `camelCase` (e.g. `findById`)
- Database tables: `snake_case` plural (e.g. `users`)
- Route names: `api.v1.{module}.{resource}.{action}` (e.g. `api.v1.auth.register`)

## Architecture
- Controllers: `final readonly __invoke` -- no business logic
- Actions: `final readonly` with `handle()` method -- single responsibility
- Repositories: read-only (findById, paginate). Writes use Eloquent in actions
- Models in `Modules/{Module}/Models/`. Use `HasDefaultBehavior` (ULID, soft deletes, date format)

## API
- All responses: `JsonResponse` or `ResourceCollection::additional()->response()`
- All errors: `ProblemResponse` (RFC 9457) -- `{type, title, status, message, detail}`
- Date format: `Y-m-d H:i:s`
- Locale via `Accept-Language` header (en, id)
- No `success` boolean in responses
- No `JsonDataResponse` or `ApiResponser` trait

## Attributes
Use PHP 8.4 attributes over docblock properties:
- `#[Fillable([...])]` on models for mass assignment
- `#[Hidden([...])]` on models for hidden fields
- Scramble: `#[Group]`, `#[Endpoint]`, `#[Response]` on controllers

## Rate Limiting
Three tiers in `config/rate-limiting.php`:
- `auth`: 5/min per email + 10/min per IP
- `authenticated`: 120/min
- `api`: 60/min

## Testing
- Pest 4 with `RefreshDatabase`
- `beforeEach` seeds `RoleSeeder`
- Feature tests for each CRUD + auth flow
- Unit tests for action classes
- Every change must have a corresponding test

## Code Quality
- Format: `./vendor/bin/pint --format agent`
- Static analysis: `./vendor/bin/phpstan analyse --memory-limit=512M`
- Do not use `@phpstan-ignore` comments
- Do not modify `phpstan.neon`
- No `dd()`, `dump()`, `console.log()` in committed code
