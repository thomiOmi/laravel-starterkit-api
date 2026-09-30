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
- Route names: `api.{version}.{module}.{name}` (e.g. `api.v1.iam.auth.register`, `api.v1.iam.user.index`)

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

- Format: `./vendor/bin/pint --dirty --format agent` (preset `laravel` + `declare_strict_types`, `strict_comparison`, `no_superfluous_elseif`, `no_useless_else`, `array_push`, `backtick_to_shell_exec`, `visibility_required`, `modernize_types_casting`; `config/database.php` excluded via `notPath`)
- Static analysis: `./vendor/bin/phpstan analyse --memory-limit=512M` (level max, test files included via `pest-plugin-phpstan`; no baseline, no `@phpstan-ignore`)
- Type coverage: `php artisan test --coverage --type-coverage --min=100 --memory-limit=512M`
  - `--memory-limit=512M` is required: phpunit runs as a child process that ignores `-d memory_limit`; the type-coverage plugin applies it via `ini_set` in-process
- Rector dry run: `composer rector:dry` (`PestSetList::CODING_STYLE`, `withComposerBased(laravel: true, phpunit: true)`, PHP 8.4 sets, prepared sets for deadCode/codeQuality/typeDeclarations/privatization/earlyReturn/codingStyle, and Laravel sets incl. `LARAVEL_CODE_QUALITY`/`LARAVEL_COLLECTION`/`LARAVEL_FACTORIES`/`LARAVEL_IF_HELPERS`), part of `composer ci:check`; skips `AddArrowFunctionReturnTypeRector`, `AddHasFactoryToModelsRector`, `CarbonToDateFacadeRector`, `tests/Architecture/ArchitectureTest.php`, and `config/database.php`
- Clear PHPStan cache with `phpstan clear-result-cache` (prefix `PAO_DISABLE=1` — the `laravel/pao` wrapper breaks non-analyse commands)
- No `dd()`, `dump()`, `console.log()` in committed code
- Production security gate: `php artisan security:check`

## PHP Platform

`require.php` and `config.platform.php` are a coupled pair and must change together.

- `require.php` is `">=8.4.1"`
- `config.platform.php` is `"8.4.1"`

`8.4.1` is the real floor imposed by the dependency tree: 24 locked packages require `>=8.4.1` (16 Symfony packages, PHPUnit, and others). Two consequences:

- Do not set it to the exact current patch. A patch-level pin makes Composer reject any package needing a later `8.4.x` or 8.5, even though CI runs 8.4 and 8.5.
- Do not set it to `"8.4"`. Composer normalises that to `8.4.0`, which fails those `>=8.4.1` requirements and breaks resolution.

## Dev Tooling Provenance

Some tools are invoked directly but are not listed in `require-dev`, because a declared direct dependency requires them with a hard constraint. Do not "fix" this by adding them, that only creates a second constraint to maintain.

- `phpstan/phpstan` - required by `larastan/larastan` and `rector/rector`, both at `^2.2.14`. Larastan is a PHPStan extension and cannot exist without it.
- `pestphp/pest-plugin-arch` - required by `pestphp/pest` at `^5.0.0`. Used by `tests/Architecture/ArchitectureTest.php`.
- `pestphp/pest-plugin-mutate` - required by `pestphp/pest` at `^5.0.2`. Unused for now; mutation gates are suspended.

The Pest plugins deliberately sit at mixed minor versions (pest is 5.2.1 while `pest-plugin-agent`, `-arch`, and `-profanity` are still 5.0.0). That is each plugin's own release cadence, not drift. `^5.0` is the correct constraint for all of them; pinning them to a common minor would be unsatisfiable.

## Dependency Audit

CI runs `composer audit --abandoned=report`, not a bare `composer audit`.

A bare `composer audit` exits 1 whenever the lock contains an abandoned package, even with zero security advisories. `symplify/rule-doc-generator-contracts` is abandoned upstream and is a hard requirement of both `driftingly/rector-laravel` and `pestphp/pest-plugin-rector`, both already at their latest releases, so it cannot be removed. It is dev-only, present in `packages-dev` and absent from the production `packages` set.

`--abandoned=report` keeps the package visible in the job log while exiting 0. Do not switch to `--abandoned=ignore`; that would silence any future abandoned package with no trace.

