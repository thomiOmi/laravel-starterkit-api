# AI Instructions for Laravel Starterkit API

## Project Overview

Laravel 13 API starter kit with modular DDD architecture. Key packages: Sanctum, Spatie Permission, Socialite, Pennant. PHP 8.4 strict.

## Architecture Rules

- Controllers: `final readonly` invokable classes (`__invoke`). No business logic.
- Actions: `final readonly` classes with `handle()` method. Single responsibility.
- Repositories: Read-only (`findById`, paginate). Writes use Eloquent directly in actions.
- Models: `Modules/{Module}/Models/`. Use `HasDefaultBehavior` trait (ULID, SoftDeletes, date format).
- Responses: `new JsonResponse([...], status)` or `ResourceCollection::additional()->response()` for paginated.
- Errors: `ProblemResponse` (RFC 9457) -- `{type, title, status, message, detail, errors?}`.
- No `JsonDataResponse` or `ApiResponser` trait -- those have been removed.

## Code Conventions

- `declare(strict_types=1)` on every PHP file.
- Docstrings on all functions, methods, classes.
- Route names: `api.v1.{module}.{resource}.{action}` (e.g. `api.v1.auth.register`).
- Use `create`/`delete` not `store`/`destroy` in route/action names.
- Use `__('general.*')` not `__('messages.*')`.
- Date format: `Y-m-d H:i:s`.
- Use `#[Fillable]`, `#[Hidden]` PHP attributes on models (not docblock properties).
- All controllers must have Scramble doc attributes (`#[Group]`, `#[Endpoint]`, `#[Response]`).

## Rate Limiting

Three tiers: `auth` (5/min per email + 10/min per IP), `authenticated` (120/min), `api` (60/min). Configurable via `config/rate-limiting.php` and `.env`.

## Testing

- Pest 4 with `RefreshDatabase`.
- `beforeEach` seeds `RoleSeeder`.
- Feature tests for each CRUD operation + auth flow.
- Unit tests for action classes.
- Run: `php artisan test --compact`.

## Cache

- Repository: `Cache::remember` with TTL (User: 300s, Role/Permission: 60s).
- Invalidation: `Cache::forget()` in CUD actions.

## Key Modules

- **Auth**: Register, login, logout, email verification, password reset, social login (Google/GitHub), device management.
- **User**: CRUD, bulk delete/restore.
- **Role**: CRUD + bulk, permission CRUD.

## Module Generator

`php artisan make:module {name}` with flags: `--force`, `--except`, `--event`, `--repository`, `--action`, `--filter`, `--migration`, `--factory`, `--seeder`.
