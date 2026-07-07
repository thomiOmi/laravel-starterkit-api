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
- Then run: `vendor/bin/phpstan analyse --memory-limit=2G` (or `PAO_FORCE=true vendor/bin/phpstan analyse --memory-limit=2G` for JSON output)
- Then run type coverage: `php -d memory_limit=2G artisan test --type-coverage` (or `composer type-coverage`)
- Run tests: `php artisan test --compact` (or `PAO_FORCE=true vendor/bin/pest --compact` for JSON output)
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
