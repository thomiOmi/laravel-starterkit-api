---
paths:
  - 'tests/**'
  - 'modules/*/Tests/**'
---

# Tests

## Goal

Structured Pest tests with typed helpers; module tests self-contained in `modules/*/Tests/`, shared app tests in `tests/`, all filterable by group.

## Rules

1. Every test file MUST use `describe()` blocks and `it()` (never bare `test()`); names describe behavior, not implementation
2. Groups: shared tests are grouped in `tests/Pest.php` (`app`, `feature`, `unit`, `arch`); module tests tag `->group('module:{module}')` per test plus the `feature`/`unit` group from `tests/Pest.php`; use smoke/slow/integration tags sparingly. Filter with `vendor/bin/pest --group=app` (shared only), `--group=feature` (all feature tests), `--group=module:iam` (IAM only). See https://pestphp.com/docs/grouping-tests
3. Use typed helpers from `tests/Helpers.php` (`assertSuccessResponse`, `assertProblemResponse`, `assertPaginatedResponse`, `loginAsUser`, `loginAsRole`, `responseData`, `artisanCommand`) instead of inline `getData()`/`artisan()`
4. Shared tests MAY import module classes directly (models, factories, seeders, contracts, enums) - ArchitectureTest allows `Tests` to use `Modules\*\*`. `tests/Helpers.php` stays as a convenience seam for shared helpers, not a hard boundary. Module tests stay self-contained in `modules/*/Tests/` and may import their own module plus other modules' public API seam (models, contracts)
5. Every code change requires tests; supported suites today: unit, feature, profanity (coverage/mutation/type-coverage gates temporarily suspended, scripts removed). Quality flow: `composer lint` (pint) -> `composer rector:dry` (rector) -> `composer types:check` (phpstan) -> `composer test` (pest) -> `composer test:profanity`; `composer ci:check` runs all in order
6. RefreshDatabase for feature tests; beforeEach seeds roles, `forgetCachedPermissions()`, creates admin via `loginAsUser`; reusable helpers go to `tests/Helpers.php` (3+ files), named datasets to `tests/Datasets/{Name}.php` (2+ uses)
7. ArchitectureTest (tests/Architecture/ArchitectureTest.php) is the single source of truth for conventions; assertion changes require human approval (report first, do not auto-fix)
8. Asserting a literal config value: always pass the default argument. `config('modules.modules.iam.active', false)` is inferred by larastan as `bool`, so `expect(...)->toBeTrue()` is fine. Omitting the default (`config('modules.modules.iam.active')`) lets larastan resolve the exact literal from `config/modules.php`, and phpstan flags `pest.expectation.redundant` ("assertion is redundant" on `Expectation<true>`). Also avoid asserting an already-known literal directly (`expect(true)->toBeTrue()` is redundant). Because rector loads `phpstan.neon` but ignores its extensions (larastan is not loaded), rector will happily rewrite `$this->assertTrue(...)` to `expect(...)` even when phpstan considers the result redundant - so write the chained `expect()` form with default args from the start. See https://getrector.com/documentation/config-configuration#content-phpstan-integration.
9. Writing style follows the pest-testing skill: feature-first, factories over manual creation, datasets to avoid duplication, specific assertions (`assertOk()` not `assertStatus(200)`), fakes over mocks. See https://github.com/matula/laravel-claude-marketplace/tree/main/pest-testing

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