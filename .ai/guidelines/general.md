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

## Project Philosophy

| Principle | Meaning |
|---|---|
| **Production-ready, not overengineered** | Every abstraction must solve a real problem. Do not build config files, middleware, or services for scenarios that do not exist yet. Prefer inline code with a default over a configurable flag that is never changed. |
| **Laravel-native first** | Use built-in Laravel features before introducing custom code or new dependencies. Convention over configuration is the default — only extract to config when the value changes between environments or is consumed by 3+ call sites. |
| **Module isolation** | Modules communicate through contracts, not through direct imports. Keep the core (IAM) minimal and let feature modules extend it. |
| **Explicit over magic** | Route middleware, authorization, and validation should be visible in the route definition and Form Request, not hidden in service providers or auto-discovery. |
| **Maintainability over cleverness** | Favor flat structures, final readonly classes, and explicit type hints. Code is written once and read many times. |

## API Convention

| Aspek | Detail |
| --- | --- |
| Base URL | `/api/v1/...` (lowercase) |
| Auth | `Authorization: Bearer {token}` (Sanctum) |
| Response | `SuccessResponse` / `ProblemResponse` — `{status, title?, detail?, data, meta?}` (NO `success` boolean) |
| Error | `ProblemResponse` — RFC 9457 |
| Date format | `Y-m-d H:i:s` |
| Route names | `v1.{module}.{name}` |

## Testing Rules

- Pest feature tests with `RefreshDatabase` trait
- `beforeEach`: seeds roles (web + sanctum guards), calls `forgetCachedPermissions()`, creates admin with `loginAsUser()`
- Test each CRUD operation: list, create, view, update, delete, unauthorized access
- Unit test per Action class (test business logic in isolation)
- Use custom expectations: `toBeSuccessResponse(status)`, `toBeProblemResponse(status)`, `toBePaginated()`
- Parallel test: `php artisan test --compact --parallel`

## Code Quality Rules

- After writing PHP code, run: `./vendor/bin/pint --dirty --format agent`
- Then run: `vendor/bin/phpstan analyse --memory-limit=2G`
- Then run type coverage: `php -d memory_limit=2G artisan test --coverage`
- Run tests: `php artisan test --compact`
- Fix all errors in code (do NOT modify `phpstan.neon`)
- Do NOT use `@phpstan-ignore` comments — fix the root cause instead
- All datetime fields in API responses **MUST** use `Y-m-d H:i:s` format
- Follow existing code conventions — check sibling files before creating new ones
- Every change must have a corresponding test
- `declare(strict_types=1)` on every PHP file
- `final readonly` for Action / Controller / Payload classes
- PHP 8 attributes (`#[Fillable]`, `#[Hidden]`, `#[UseFactory]`) over `$fillable` / `$hidden`
- `config()->string()` / `->integer()` / `->boolean()` / `->array()` for config access
- Prefer `match` expression over `switch`
- Use Enum value as default in migration: `$table->string('status')->default(StatusEnum::Pending->value)`
- Cast enum columns to Enum type in Model: `'status' => StatusEnum::class`
- Do NOT chain migration commands with `&&` or `;` — they may get identical timestamps
- Use Context7 (`context7_query-docs`) for library docs when Laravel Boost `search-docs` does not have the library

## Agentic Development (Laravel Boost & Agent Skills)

- **Laravel Boost**: Accelerates development with framework-specific guidelines and MCP tools (`search-docs`, `database-schema`, etc.).
- **Agent Skills**: Uses the [agentskills.io](https://agentskills.io) format for domain-specific expertise.
- **Location**: Guidelines in `.ai/guidelines/`, Skills in `.ai/skills/`.
- **Activation**: Guidelines are loaded upfront; Skills are activated on-demand via triggers (`skill` tool).
- **Update**: Run `php artisan boost:install -n` to sync/re-apply all guidelines and skills.

## File Ownership Rules

| File/Dir | Managed by | Edit via |
|---|---|---|
| `AGENTS.md` | Laravel Boost (auto-generated) | **DO NOT** edit directly — edit `.ai/guidelines/` or `.ai/skills/` instead |
| `.ai/guidelines/` | You (survives `boost:install`) | Edit directly — source files for AGENTS.md guidelines section |
| `.ai/skills/` | You (survives `boost:install`) | Edit directly — Boost-managed agent skills |
| `.agents/` | AI agent's skill/rules system | **DO NOT** edit directly — let the AI agent manage it |
