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

| Aspect | Detail |
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

### Composer Scripts

| Command | Description |
|---|---|
| `composer lint` | Auto-fix code style with Pint |
| `composer lint:check` | Check code style without modifications |
| `composer types:check` | Run PHPStan static analysis |
| `composer test` | Run lint:check + types:check + test suite |
| `composer test:quality` | Run lint:check + types:check + tests with code & type coverage (min 100%) |
| `composer test:mutation` | Run mutation testing |
| `composer test:profanity` | Run profanity checks on test files |
| `composer ci:check` | Full CI pipeline (quality + profanity) |

### Testing Organization

| Concern | Location | When |
|---|---|---|
| Custom expectations | `tests/Expectations.php` | Reusable `expect()->extend()` / `expect()->pipe()` |
| Global helpers | `tests/Helpers.php` | Functions reused in 3+ test files |
| File helpers | Inline in test file | Single-file use only |
| Named datasets | `tests/Datasets/{Name}.php` | Used via `->with('name')` in 2+ tests |
| Inline datasets | Inside `dataset()` in test file | Single `->with()` usage only |

### describe() / it() / group()

- **`describe()`** — Every test file MUST use `describe()` blocks to group logical concerns. Nesting is allowed for sub-grouping (e.g., `describe('fillable')` inside `describe('PersonalAccessToken')`). Description describes the unit under test or the behavior.
- **`it()`** — All test cases use `it()` (never bare `test()`). Name describes expected behavior, not implementation — e.g., `it('returns 422 when email is missing')`.
- **`group()`** — Use `->group('name', ...)` for cross-cutting categorization:
  - `'smoke'` — critical-path tests for deployment validation
  - `'slow'` — tests that take >5s
  - `'integration'` — tests that hit external services
  - `'module:{name}'` — e.g., `module:iam`, `module:billing`
  - Add new groups sparingly; prefer `describe()` + `--filter` for most filtering needs

## Code Quality Rules

- After writing PHP code, run: `composer lint` (or `./vendor/bin/pint --dirty --format agent` for dirty-only)
- Then run: `composer types:check`
- Then run type coverage: `composer test:quality`
- Run tests: `composer test` (includes lint:check + types:check + test suite)
- Before pushing: run `composer ci:check` (full quality gate)
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

## Architecture Test Rules (`tests/Architecture/ArchitectureTest.php`)

- **DO NOT** modify `ArchitectureTest.php` unless explicitly instructed by the user
- If architecture tests fail due to code changes, **report the failure to the user** and let them decide how to proceed — do NOT auto-fix or auto-ignore the rule
- The architecture test file is the project's single source of truth for conventions; changes require deliberate human approval

## Agentic Development (Laravel Boost & Agent Skills)

- **Laravel Boost**: Accelerates development with framework-specific guidelines and MCP tools (`search-docs`, `database-schema`, etc.).
- **Agent Skills**: Uses the [agentskills.io](https://agentskills.io) format for domain-specific expertise.
- **Location**: Guidelines in `.ai/guidelines/`, Skills in `.ai/skills/`.
- **Activation**: Guidelines are loaded upfront; Skills are activated on-demand via triggers (`skill` tool).
- **Update**: Run `php artisan boost:update --discover` to sync/re-apply all guidelines and skills.

## File Ownership Rules

| File/Dir | Managed by | Edit via |
|---|---|---|
| `AGENTS.md` | Laravel Boost (auto-generated) | **DO NOT** edit directly — edit `.ai/guidelines/` or `.ai/skills/` instead |
| `.ai/guidelines/` | You (survives `boost:update`) | Edit directly — source files for AGENTS.md guidelines section |
| `.ai/skills/` | You (survives `boost:update`) | Edit directly — Boost-managed agent skills |
| `.agents/` | AI agent's skill/rules system | **DO NOT** edit directly — let the AI agent manage it |
