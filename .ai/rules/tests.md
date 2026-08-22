---
paths:
  - 'tests/**'
  - 'modules/*/tests/**'
  - 'tests/**/*.php'
  - 'modules/*/tests/**/*.php'
---

# Tests

## Goal

Structured Pest tests with typed helpers; module tests self-contained in `modules/*/tests/`, shared app tests in `tests/`, all filterable by group.

## Rules

1. Every test file MUST use `describe()` blocks and `it()` (never bare `test()`); names describe behavior, not implementation
2. Groups: shared tests are grouped in `tests/Pest.php` (`app`, `feature`, `unit`, `arch`); module tests tag `->group('module:{module}')` per test plus the `feature`/`unit` group from `tests/Pest.php`; use smoke/slow/integration tags sparingly. Filter with `vendor/bin/pest --group=app` (shared only), `--group=feature` (all feature tests), `--group=module:iam` (IAM only). See https://pestphp.com/docs/grouping-tests
3. Use typed helpers from `tests/Helpers.php` (`assertSuccessResponse`, `assertProblemResponse`, `assertPaginatedResponse`, `loginAsUser`, `loginAsRole`, `responseData`, `artisanCommand`) instead of inline `getData()`/`artisan()`
4. Shared tests MAY import module classes directly (models, factories, seeders, contracts, enums) - ArchitectureTest allows `Tests` to use `Modules\*\*`. `tests/Helpers.php` stays as a convenience seam for shared helpers, not a hard boundary. Module tests stay self-contained in `modules/*/tests/` and may import their own module plus other modules' public API seam (models, contracts)
5. Every code change requires tests; supported suites today: unit, feature, profanity (coverage/mutation/type-coverage gates temporarily suspended, scripts removed). Quality flow: `composer lint` (pint) -> `composer rector:dry` (rector) -> `composer types:check` (phpstan) -> `composer test` (pest) -> `composer test:profanity`; `composer ci:check` runs all in order
6. RefreshDatabase for feature tests; beforeEach seeds roles, `forgetCachedPermissions()`, creates admin via `loginAsUser`; reusable helpers go to `tests/Helpers.php` (3+ files), named datasets to `tests/Datasets/{Name}.php` (2+ uses)
7. ArchitectureTest (tests/Architecture/ArchitectureTest.php) is the single source of truth for conventions; assertion changes require human approval (report first, do not auto-fix)
8. Asserting a literal config value: always pass the default argument. `config('modules.modules.iam.active', false)` is inferred by larastan as `bool`, so `expect(...)->toBeTrue()` is fine. Omitting the default (`config('modules.modules.iam.active')`) lets larastan resolve the exact literal from `config/modules.php`, and phpstan flags `pest.expectation.redundant` ("assertion is redundant" on `Expectation<true>`). Also avoid asserting an already-known literal directly (`expect(true)->toBeTrue()` is redundant). Because rector loads `phpstan.neon` but ignores its extensions (larastan is not loaded), rector will happily rewrite `$this->assertTrue(...)` to `expect(...)` even when phpstan considers the result redundant - so write the chained `expect()` form with default args from the start. See https://getrector.com/documentation/config-configuration#content-phpstan-integration.
9. Writing style follows the pest-testing skill, applied inline here:
   - Create tests with `php artisan make:test --pest {Name}`; never include the suite directory in the name (`SomeFeatureTest`, not `Feature/SomeFeatureTest` - the latter doubles the path)
   - Feature tests before unit tests; factories over manual model creation; datasets to avoid duplication (inline when used once, named in `tests/Datasets/` when reused)
   - Specific assertions over `assertStatus`: `assertSuccessful()` not `assertStatus(200)`, `assertNotFound()` not `assertStatus(404)`, `assertForbidden()` not `assertStatus(403)`
   - Fakes over mocks; when mocking, import the helper (`use function Pest\Laravel\mock;`)
   - Run the minimum needed before finishing: `php artisan test --compact --filter=testName`

## Forbidden

- No bare `test()` calls
- No modifying ArchitectureTest.php without explicit approval
- No `--coverage`/`--type-coverage`/`--mutate` gates until re-enabled

## Example

```php
describe('register', function () {
    it('creates a user', function () {
        $response = $this->postJson('/api/v1/iam/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        assertSuccessResponse($response);
    })->group('module:iam');
});
```

## Isolate nwidart module singletons in fixture tests
Any test overriding modules.* config (paths.modules or activators.file.statuses-file) MUST call forgetModuleSingletons() from tests/Helpers.php, which drops both RepositoryInterface and ActivatorInterface singletons. FileActivator memoizes the statuses-file path on first resolution, so forgetting only 'modules' lets writes land in the real modules_statuses.json. Prefer bindFixtureModulePaths($root) when the fixture uses the standard {root}/modules + {root}/statuses.json layout. After running module tooling tests, verify with: git diff --exit-code modules_statuses.json

## Modular Pest testing conventions (strict Unit/Feature split)
Unit = pure logic only: no DB traits (RefreshDatabase/DatabaseTransactions), no HTTP calls, no app bootstrapping. Database-backed behaviour belongs in the module's Feature suite, where tests/Pest.php applies RefreshDatabase automatically (never add it per-file there). Prefer describe()/it() with datasets (->with([...])) over duplicated assertions. Assert envelopes via assertSuccessResponse/assertProblemResponse helpers; 422 responses expose an errors member so ->assertJsonValidationErrors([...]) works. Mock third parties at facade/driver boundaries (Socialite::shouldReceive), never final action classes.
