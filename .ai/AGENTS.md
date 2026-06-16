# AI Instructions for Laravel Starterkit API

## Project Overview

Laravel 13 API starter kit with modular DDD architecture. PHP 8.4 strict. Sanctum token auth, Spatie Permission, Socialite, Pennant.

## Architecture Rules

- Controllers: `final readonly` invokable classes (`__invoke`). No business logic.
- Actions: `final readonly` classes with `handle()` method.
- Repositories: Read-only (`findById`, paginate). Writes use Eloquent directly in actions.
- Responses: `new JsonResponse([...], status)` or `ResourceCollection::additional()->response()`.
- Errors: `ProblemResponse` (RFC 9457) — `{type, title, status, message, detail, errors?}`.

## Code Conventions

- `declare(strict_types=1)` on every file. Docstrings on all functions.
- Route names: `api.v1.{module}.{resource}.{action}`. Use `create`/`delete` not `store`/`destroy`.
- Use `__('general.*')` not `__('messages.*')`. Date format: `Y-m-d H:i:s`.
- `#[Fillable]`, `#[Hidden]` attributes on models. Scramble: `#[Group]`, `#[Endpoint]`, `#[Response]`.

## Laravel Boost MCP Tools

Use these tools when working with the codebase:
- `search-docs` — Query Laravel docs for version-specific guidance
- `database-schema` — Inspect table structure
- `database-query` — Run read-only SQL queries
- `get-absolute-url` — Resolve full URLs for routes
- `browser-logs` — Read browser errors
- `last-error` / `read-log-entries` — Debug application errors

## Rate Limiting

Three tiers: `auth` (5/min per email + 10/min per IP), `authenticated` (120/min), `api` (60/min). Config via `config/rate-limiting.php` and `.env`.

## Testing

Pest 4 with `RefreshDatabase`. `beforeEach` seeds `RoleSeeder`. Feature tests for CRUD + auth. Unit tests for actions.

## Modules

- **Auth**: Register, login, logout, email verification, password reset, social login (Google/GitHub), device management.
- **User**: CRUD, bulk delete/restore.
- **Role**: CRUD + bulk, permission CRUD.

## Module Generator

`php artisan make:module {name}` with flags: `--force`, `--except`, `--event`, `--repository`, `--action`, `--filter`, `--migration`, `--factory`, `--seeder`.
