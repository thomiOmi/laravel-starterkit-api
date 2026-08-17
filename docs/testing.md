# Testing Conventions

## Composer Scripts

| Command | Description |
|---|---|---|
| `composer setup` | Install dependencies and prepare the application |
| `composer setup:ci` | Prepare the application for CI (copy .env, key:generate, sqlite, migrate) |
| `composer lint` | Auto-fix code style (`@php vendor/bin/pint --parallel`) |
| `composer lint:staged` | Auto-fix code style for staged files only (`@php vendor/bin/pint --parallel --dirty`) |
| `composer lint:check` | Check code style without modifications |
| `composer types:check` | Run PHPStan static analysis (level max, includes test files via `pest-plugin-phpstan`) |
| `composer test` | Run the test suite: `vendor/bin/pest --parallel` (unit + feature, with baseline) |
| `composer test:profanity` | Run profanity checks on test files |
| `composer test:profile` | Run the suite and report the slowest tests (`vendor/bin/pest --profile`) |
| `composer ci:check` | Full CI pipeline - runs `lint:check`, `rector:dry`, `types:check`, `test`, then `test:profanity` |

### Tooling notes

- Test scripts run `vendor/bin/pest` directly (not `php artisan test`), so the process runs in a single PHP context: `--memory-limit=` is consumed by the type-coverage plugin (a plugin flag, not part of the Pest CLI API reference), and `--configuration` can point at any XML file without Collision overriding it.
- The baseline is declared in `phpunit.xml` via `<source baseline="phpunit.baseline.xml">`, so it applies to every Pest run (including `test:snapshot` and `test:profanity`). It whitelists known deprecations/notices so they do not fail the run; only **new** deprecations/notices fail. Regenerate it with `vendor/bin/pest --no-tia --generate-baseline=phpunit.baseline.xml` after a `composer update` brings in vendor deprecations (or when you intentionally fix/accept them). The file is committed.
- `phpunit.xml` is strict: `failOnDeprecation`, `failOnNotice`, `failOnPhpunitDeprecation`, `failOnPhpunitNotice`, `failOnEmptyTestSuite`, `failOnWarning`, `failOnRisky`, and `beStrictAbout*` are all enabled. `database/` and `modules/*/Database` stay excluded from the coverage `<source>` (migration `down()` methods and unused seeders/factory states are not exercised by tests; revisit when module tests land).
- PHPStan output is wrapped by `laravel/pao` in a JSON envelope when an AI agent is detected. Set `PAO_DISABLE=1` to see raw output; use `phpstan clear-result-cache` (with `PAO_DISABLE=1`) to clear the result cache.
| `composer dev` | Run all dev processes concurrently (server, queue, logs) |

The recommended pre-push workflow:

```bash
composer lint          # fix code style
composer test          # unit + feature tests
composer test:profanity  # profanity checks
```

## Directory Structure

```text
tests/
├── .pest/                    # Pest snapshot storage + shard state (generated, do not edit)
├── Architecture/
│   └── ArchitectureTest.php  # arch() suite - source of truth for code conventions
├── Datasets/                 # Named datasets for ->with('name') used in 2+ tests
├── Feature/                  # HTTP feature tests (RefreshDatabase)
│   ├── Console/              # Artisan command tests
│   └── Http/                 # Middleware and form request tests
├── Unit/                     # Unit tests (no RefreshDatabase)
│   ├── Concerns/
│   ├── Enums/
│   ├── Http/                 # Middleware, responses, exceptions, requests
│   ├── Models/
│   ├── Notifications/
│   └── Support/
├── Expectations.php          # Pest expectation pipes (snapshot timestamp stripping)
├── Helpers.php               # Typed assertion helpers (assertSuccessResponse, etc.)
├── Pest.php                  # TestCase binding per directory + global beforeEach
└── TestCase.php              # Base test case (abstract)
phpunit.xml                   # Strict PHPUnit configuration + coverage baseline
phpunit.baseline.xml          # Whitelisted deprecations/notices
```

Notes:

- `Pest.php` also registers module test paths: `modules/*/tests/{Feature,Unit}`. `modules/IAM/tests/` is currently empty.
- `phpunit.xml` is strict (`failOnDeprecation`, `failOnNotice`, etc.) and declares the baseline — see Tooling notes above.

## Expectations (`tests/Expectations.php`)

Custom expectation API extensions. Only `expect()->pipe()` customizations live here — custom `expect()->extend()` methods are **not** recognized by PHPStan (`pest-plugin-phpstan` only understands built-in matchers), so response-level assertions are typed functions in `tests/Helpers.php` instead.

| Expectation | Description |
|---|---|
| `toMatchSnapshot()` | (pipe) Strip dynamic `timestamp` before comparing against stored `.snap` file |

Pest re-binds the pipe closure to the expectation at runtime, so `$this` refers to `Pest\Expectation` inside it (documented with a `@var Expectation<mixed> $this` docblock). `pest-plugin-phpstan` only types `$this` for test closures, not pipe closures, so the `$this->value` accesses carry `@phpstan-ignore-next-line` — the same pattern Pest core itself uses in `src/Concerns/Pipeable.php`.

## Response assertion helpers (`tests/Helpers.php`)

Typed functions for asserting API response envelopes (PHPStan-safe, unlike custom expectations).

| Function | Parameters | Description |
|---|---|---|
| `assertSuccessResponse()` | `int $status = 200, ?string $title = null` | Assert JSON: `status`, `title`, `detail`, `data` — skips body check for 204/205 |
| `assertProblemResponse()` | `int $status = 422, ?string $type = null` | Assert `Content-Type: application/problem+json`, JSON structure: `type`, `title`, `status`, `detail`, `timestamp` |
| `assertPaginatedResponse()` | — | Assert JSON structure: `status`, `data`, `meta` with `per_page` and `has_more` |
| `assertHasTraceId()` | — | Assert `X-Trace-ID` header present and non-empty |
| `assertSunsetHeader()` | `string $date` | Assert `Sunset` header matches RFC 7231 format |

Each helper takes a `TestResponse` and returns it, so assertions can be chained:

```php
$response = $this->postJson('/api/v1/users', $payload);

assertSuccessResponse($response, 201);
assertProblemResponse($response, 422, 'validation-error');
```

## Helpers (`tests/Helpers.php`)

Global helper functions for authentication and API responses.

| Function | Returns | Description |
|---|---|---|
| `loginAsUser(?User $user, array $abilities)` | `User` | Authenticate with Sanctum (verified user). Default abilities: `['*']` |
| `loginAsUnverifiedUser(?User $user, array $abilities)` | `User` | Authenticate with Sanctum (unverified user). Default abilities: `['*']` |
| `loginAsRole(RoleEnum $role, ?User $user, array $abilities)` | `User` | Authenticate a verified user and assign a role. Requires the role to exist (seed `RoleSeeder` in the test) |
| `loginAsSuperAdmin(?User $user, array $abilities)` | `User` | Authenticate a verified user with the `super-admin` role |
| `loginAsAdmin(?User $user, array $abilities)` | `User` | Authenticate a verified user with the `admin` role |
| `loginAsUserRole(?User $user, array $abilities)` | `User` | Authenticate a verified user with the basic `user` role |
| `responseData(Response $response)` | `array` | Decode JSON response content — use instead of `$response->getData(true)` (satisfies PHPStan) |
| `assertSuccessResponse()` | `TestResponse` | Assert the success envelope (see table above) |
| `assertProblemResponse()` | `TestResponse` | Assert the RFC 9457 problem details envelope (see table above) |
| `assertPaginatedResponse()` | `TestResponse` | Assert the paginated envelope (see table above) |
| `assertHasTraceId()` | `TestResponse` | Assert the `X-Trace-ID` header (see table above) |
| `assertSunsetHeader()` | `TestResponse` | Assert the `Sunset` header (see table above) |
| `artisanCommand(TestCase $test, string $command, array $parameters = [])` | `PendingCommand` | Run an artisan command from a feature test — use instead of `$this->artisan()` (resolves the `PendingCommand\|int` union) |

### File-level helpers

If a helper is only used in one test file, define it at the top of the file (outside any `describe`/`it`). Only extract to `tests/Helpers.php` when reused in 3+ test files.

```php
// At the top of the test file
function makeFilter(array $query = [], array $config = []): TestFilter
{
    return new TestFilter($query, $config);
}
```

## Global behavior coverage

Shared, app-wide behavior is covered in dedicated files so every global pipeline change is tested:

| File | Covers |
|---|---|
| `tests/Unit/Concerns/HasDefaultBehaviorTest.php` | Model default behavior: ULID keys, string key type, `Y-m-d H:i:s` date serialization |
| `tests/Unit/Http/Exceptions/ExceptionHandlerTest.php` | All 8 exception handler render rules (validation, auth, forbidden, not found, rate limit, bad request, generic HTTP, internal error) — `smoke` group |
| `tests/Feature/Http/Middleware/GlobalApiMiddlewareTest.php` | Global API pipeline: 404 problem responses, `X-Trace-ID`, security headers on error responses — `smoke` group |

Exception responses are decorated with `X-Trace-ID` and security headers via `$exceptions->respond(...)` in `bootstrap/app.php`, because middleware that sets headers after `$next()` never runs when a request throws.

## Datasets (`tests/Datasets/`)

Named datasets used via `->with('name')`. Created with `php artisan pest:dataset {Name}`.

| File | Rows | Used by |
|---|---|---|
| `FilterOperators.php` | 8 (`eq`, `neq`, `gt`, `gte`, `lt`, `lte`, `like`, `in`) | `BaseQueryBuilderTest` |
| `SecurityFailCases.php` | 14 (`APP_DEBUG`, `APP_ENV`, `APP_URL`, etc.) | `ProductionSecurityCheckTest` |

### Adding a new dataset

```bash
php artisan pest:dataset MyDataset
```

Edit the generated file in `tests/Datasets/MyDataset.php`. Use in tests:

```php
it('handles each case', function (string $input, string $expected) {
    // ...
})->with('myDataset');
```

Inline datasets are acceptable only when the dataset is used by a single `->with()` call.

## describe() / it() / group()

### describe()

Every test file must use `describe()` blocks to group logical concerns. Nesting is allowed for sub-grouping.

```php
describe('PaginationRequest', function () {
    describe('per page', function () {
        it('accepts values between 1 and 100', function () { ... });
    });

    describe('page number', function () {
        it('defaults to 1', function () { ... });
    });
});
```

### it()

All test cases use `it()` — never bare `test()`. Name must describe expected behavior, not implementation.

✅ `it('returns 422 when email is missing')`  
✅ `it('caches locale for 3600 seconds')`  
❌ `it('testValidation')`  
❌ `test('email validation')`  

### group()

Use `->group()` for cross-cutting categorization. Useful for CI pipelines and targeted test runs.

```php
it('sends email via sendgrid', function () {
    // ...
})->group('integration', 'slow');

it('loads the landing page', function () {
    // ...
})->group('smoke');
```

Predefined groups:

| Group | Purpose |
|---|---|
| `'smoke'` | Critical-path tests for deployment validation |
| `'slow'` | Tests that take >5 seconds |
| `'integration'` | Tests that hit external services |
| `'module:{name}'` | Module-scoped tests (e.g., `module:iam`, `module:billing`) |

Run with: `php artisan test --group=smoke`

Add new groups sparingly. Prefer `describe()` + `--filter` for most filtering needs.

## Snapshot Testing

Snapshots are stored in `tests/.pest/snapshots/**/*.snap`. The Expectation Pipe in `Expectations.php` strips dynamic `timestamp` fields before comparison so snapshots are stable.

```php
it('matches snapshot for basic response', function () {
    $response = (new SuccessResponse(data: ['id' => 1]))->toResponse(new Request);

    expect($response->getContent())->toMatchSnapshot();
});
```

### Updating snapshots

```bash
composer test:snapshot
```

Commit the updated `.snap` files — they are the baseline and must be tracked in git.

## Agent Probe (`--agent`)

`pestphp/pest-plugin-agent` runs a one-off verification snippet inside a full Pest test — `RefreshDatabase`, factories, Laravel fakes, and authentication all work exactly as in a feature test:

```bash
vendor/bin/pest --agent='$user = \Modules\IAM\Database\Factories\UserFactory::new()->create(); $this->actingAs($user)->getJson("/api/v1/me")->assertOk();'
```

Rules:

- Every class must be fully qualified (the generated test file contains no `use` imports)
- Directory-scoped `beforeEach()` hooks do not run for the snippet — inline required setup at the top
- Keep each snippet focused on one behavior; pass `--agent` multiple times for several probes
- A probe is a verification tool, not a substitute for your test suite — write a real test in `tests/Feature` when the behavior deserves a lasting regression guard

## TIA (`--tia`)

Test Impact Analysis (built-in Pest 5) re-runs only the tests affected by your changes and replays the rest from cache. It is opt-in and runs only when the flag is passed explicitly (it is not auto-enabled in `tests/Pest.php`):

```bash
composer test:tia         # explicit TIA run
php artisan test --tia    # explicit TIA run
php artisan test          # always a full run
```

- The first run records the dependency graph and requires a coverage driver: `XDEBUG_MODE=coverage vendor/bin/pest --tia` (about 2x slower than a normal run). The graph is stored in `~/.pest/tia/{project}`
- Subsequent runs replay cached results: the 310-test suite drops from ~50s to ~3s
- After a large refactor, re-record the graph with `vendor/bin/pest --tia --fresh`
- `ci:check` never uses TIA — CI always runs the full suite

## Optimizing Tests

- **Parallel**: `composer test` runs `vendor/bin/pest --parallel` — one process per CPU core. Each worker gets its own SQLite `:memory:` database, so tests stay isolated; do not rely on shared files or global state between tests.
- **Compact printer**: `--compact` is used only in CI (`composer ci:check` via `test`), not in `tests/Pest.php`, so local runs keep the full per-test output. It prints only failures and reduces I/O.
- **Profiling**: `composer test:profile` runs `vendor/bin/pest --profile` to list the slowest tests — use it before optimizing specific tests.
- **Sharding**: `vendor/bin/pest --parallel --update-shards` records per-class timings into `tests/.pest/shards.json` (committed). CI jobs can then run `vendor/bin/pest --ci --shard=N/TOTAL` with time-balanced distribution. Re-run `--update-shards` after adding or renaming test files, and commit the refreshed file.

## Test Placement & Isolation

| Location | Contains |
|---|---|
| `tests/` | App-layer tests (middleware, filters, requests, responses, console commands) and shared suites (`Unit`, `Feature`, `Architecture`) |
| `modules/*/tests/` | Module-internal tests (controllers, actions, resources). Registered as the `Modules` testsuite in `phpunit.xml`; excluded from PHPStan paths |

Rules:

- App-layer tests may only import the module **User model/factory** (the app's auth model) — and must route it through the `tests/Helpers.php` seam (`loginAsUser()`, `loginAsUnverifiedUser()`, `loginAsRole()`, `loginAsSuperAdmin()`, `loginAsAdmin()`, `loginAsUserRole()`), never through `Modules\*\...` imports directly.
- Module-internal tests must stay self-contained in their module and must not import other modules.
- Test fixtures shared across files go in `tests/Helpers.php`; parameterized cases use Datasets (inline when used by a single file, named when reused).
