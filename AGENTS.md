<laravel-boost-guidelines>
=== .ai/creating-skills rules ===

# Creating Custom AI Skills & Rules

This project uses the [Agent Skills](https://agentskills.io) format for domain-specific AI knowledge. Skills are loaded on-demand; guidelines are loaded upfront.

## Guidelines vs Skills

| | Guidelines | Skills |
|---|---|---|
| Location | `.ai/guidelines/*.md` | `.ai/skills/{name}/SKILL.md` |
| Loaded | Upfront, always present | On-demand, when task matches description |
| Scope | Broad conventions (e.g, coding standards, architecture) | Focused domain knowledge (e.g, testing, permissions, social auth) |

## Creating a Skill

### 1. Directory structure

```text
.ai/skills/{skill-name}/
├── SKILL.md          # Required: YAML frontmatter + metadata + instructions

├── scripts/          # Optional: executable code

├── references/       # Optional: documentation

├── assets/           # Optional: templates, resources

└── ...               # Any additional files or directories

```

### 2. SKILL.md format

```yaml
---
name: skill-name
description: Clear description of what this skill does and when to use it. Include keywords for matching.
metadata:
  version: "1.0"
---
```

Name rules:
- Lowercase letters, numbers, and hyphens only
- Max 64 characters
- Must match the parent directory name

Description rules:
- Max 1024 characters
- Describe both what and when

### 3. Progressive disclosure

Keep SKILL.md under 500 lines. Move detailed reference material to `references/`.

```markdown
See [the reference guide](references/detail.md) for full documentation.
```

More details. See [specification](https://agentskills.io/specification), [best practices](https://agentskills.io/skill-creation/best-practices), [optimizing descriptions](https://agentskills.io/skill-creation/optimizing-descriptions), [using scripts](https://agentskills.io/skill-creation/using-scripts)

## Creating a Guideline

Add `.md` files to `.ai/guidelines/`. Guidelines are loaded upfront, so keep them concise (under 100 lines).

```text
.ai/guidelines/
├── general.md           # Project conventions (tech stack, API, testing, code quality)

├── creating-skills.md   # This file

└── ...                  # Any additional guidelines

```

## Managing with Boost

Run `php artisan boost:update --discover` to install Boost-provided guidelines and skills. All custom files in `.ai/` are preserved.

## Existing Skills

| Skill | Location | Description |
|---|---|---|
| `laravel-attributes` | `.ai/skills/laravel-attributes/` | PHP 8 attributes for Laravel models, jobs, commands, form requests |
| `modular-architecture` | `.ai/skills/modular-architecture/` | Module DDD structure: Actions, Controllers, Filters, Payloads, Resources |

Use the `skill` tool to load a skill when the task matches its description. List all available skills with the `available_skills` list in the system prompt.

=== .ai/general rules ===

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
|---|---|---|
| `composer setup` | Install dependencies and prepare the application |
| `composer setup:ci` | Prepare the application for CI (copy .env, key:generate, sqlite, migrate) |
| `composer lint` | Auto-fix code style with Pint |
| `composer lint:staged` | Auto-fix code style for staged files only (pre-commit hook) |
| `composer lint:check` | Check code style without modifications |
| `composer types:check` | Run PHPStan static analysis |
| `composer test` | Run lint:check + types:check + test suite |
| `composer test:quality` | Run lint:check + types:check + tests with code & type coverage (min 100%) |
| `composer test:mutation` | Run mutation testing |
| `composer test:profanity` | Run profanity checks on test files |
| `composer ci:check` | Full CI pipeline (quality + profanity) |
| `composer dev` | Run all dev processes concurrently (server, queue, logs) |

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

- After writing PHP code, run: `composer lint:staged` (pre-commit) or `composer lint` (all files)
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

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/framework (LARAVEL) - v13
- laravel/pennant (PENNANT) - v1
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/socialite (SOCIALITE) - v5
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v5
- phpunit/phpunit (PHPUNIT) - v13
- rector/rector (RECTOR) - v2

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
