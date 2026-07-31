# Testing Conventions

## Composer Scripts

| Command | Description |
|---|---|---|
| `composer setup` | Install dependencies and prepare the application |
| `composer setup:ci` | Prepare the application for CI (copy .env, key:generate, sqlite, migrate) |
| `composer lint` | Auto-fix code style (`pint --parallel`) |
| `composer lint:staged` | Auto-fix code style for staged files only (`pint --parallel --dirty`) |
| `composer lint:check` | Check code style without modifications |
| `composer types:check` | Run PHPStan static analysis (level max, includes test files via `pest-plugin-phpstan`) |
| `composer test` | Run lint:check + types:check + `php artisan test` |
| `composer test:quality` | Run lint:check + types:check + tests with `--coverage --type-coverage --min=100 --memory-limit=512M` |
| `composer test:mutation` | Run mutation testing (`--mutate --min=100`) |
| `composer test:profanity` | Run profanity checks on test files |
| `composer ci:check` | Full CI pipeline — runs `test:quality`, `rector:dry`, then `test:profanity` |

### Tooling notes

- `php artisan test` spawns phpunit as a child process that does not inherit `-d memory_limit` flags; the type-coverage plugin applies its own limit from `--memory-limit=` passed to artisan.
- PHPStan output is wrapped by `laravel/pao` in a JSON envelope when an AI agent is detected. Set `PAO_DISABLE=1` to see raw output; use `phpstan clear-result-cache` (with `PAO_DISABLE=1`) to clear the result cache.
| `composer dev` | Run all dev processes concurrently (server, queue, logs) |

The recommended pre-push workflow:

```bash
composer lint          # fix code style
composer test:quality  # types + coverage + tests
```

## Expectations (`tests/Expectations.php`)

Custom expectations reusable across all test files.

| Expectation | Parameters | Description |
|---|---|---|
| `toBeSuccessResponse()` | `int $status = 200, ?string $title = null` | Assert JSON: `status`, `title`, `detail`, `data` — skips body check for 204/205 |
| `toBeProblemResponse()` | `int $status = 422, ?string $type = null` | Assert `Content-Type: application/problem+json`, JSON structure: `type`, `title`, `status`, `detail`, `timestamp` |
| `toBePaginated()` | — | Assert JSON structure: `status`, `data`, `meta` with `per_page` and `has_more` |
| `toHaveTraceId()` | — | Assert `X-Trace-ID` header present and non-empty |
| `toHaveSunsetHeader()` | `string $date` | Assert `Sunset` header matches RFC 7231 format |
| `toMatchSnapshot()` | (pipe) | Strip dynamic `timestamp` before comparing against stored `.snap` file |

### Adding a new expectation

```php
expect()->extend('toBeFoo', function (int $bar = 1): Expectation {
    /** @var Expectation<mixed> $this */
    /** @var TestResponse $response */
    $response = $this->value;

    $response->assertStatus($bar);

    return $this;
});
```

## Helpers (`tests/Helpers.php`)

Global helper functions for authentication.

| Function | Returns | Description |
|---|---|---|
| `loginAsUser(?User $user, array $abilities)` | `User` | Authenticate with Sanctum (verified user). Default abilities: `['*']` |
| `loginAsUnverifiedUser(?User $user, array $abilities)` | `User` | Authenticate with Sanctum (unverified user). Default abilities: `['*']` |
| `responseData(Response $response)` | `array` | Decode JSON response content — use instead of `$response->getData(true)` (satisfies PHPStan) |
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

## Datasets (`tests/Datasets/`)

Named datasets used via `->with('name')`. Created with `php artisan pest:dataset {Name}`.

| File | Rows | Used by |
|---|---|---|
| `FilterOperators.php` | 7 (`eq`, `neq`, `gt`, `gte`, `lt`, `lte`, `like`) | `BaseFilterTest` |
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
php artisan test --update-snapshots
```

Commit the updated `.snap` files — they are the baseline and must be tracked in git.
