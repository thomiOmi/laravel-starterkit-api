---
paths:
  - 'tests/**'
  - 'modules/*/Tests/**'
---

# Tests

## Goal

Structured Pest tests with typed helpers; module tests self-contained in `modules/*/Tests/`, app tests in `tests/`.

## Rules

1. Every test file MUST use `describe()` blocks and `it()` (never bare `test()`); names describe behavior, not implementation
2. Tag with `->group('module:{module}')` per module; use smoke/slow/integration tags sparingly
3. Use typed helpers from `tests/Helpers.php` (`assertSuccessResponse`, `assertProblemResponse`, `assertPaginatedResponse`, `loginAsUser`, `loginAsRole`, `responseData`, `artisanCommand`) instead of inline `getData()`/`artisan()`
4. App-layer tests may only import module models/factories through the Helpers seam; module tests stay self-contained in `modules/*/Tests/` - direct `Modules\*` imports in `tests/` are forbidden, EXCEPT Seeder imports for test seeding needs (`$this->seed(\Modules\IAM\Database\Seeders\IAMSeeder::class)`)
5. Every code change requires tests; 100% code and type coverage (quality gates: `composer lint`, `composer types:check`, `composer test:quality`, `composer ci:check`)
6. RefreshDatabase for feature tests; beforeEach seeds roles, `forgetCachedPermissions()`, creates admin via `loginAsUser`; reusable helpers go to `tests/Helpers.php` (3+ files), named datasets to `tests/Datasets/{Name}.php` (2+ uses)
7. ArchitectureTest (tests/Architecture/ArchitectureTest.php) is the single source of truth for conventions; assertion changes require human approval (report first, do not auto-fix)
8. Asserting a literal config value: always pass the default argument. `config('modules.modules.iam.active', false)` is inferred by larastan as `bool`, so `expect(...)->toBeTrue()` is fine. Omitting the default (`config('modules.modules.iam.active')`) lets larastan resolve the exact literal from `config/modules.php`, and phpstan flags `pest.expectation.redundant` ("assertion is redundant" on `Expectation<true>`). Also avoid asserting an already-known literal directly (`expect(true)->toBeTrue()` is redundant). Because rector loads `phpstan.neon` but ignores its extensions (larastan is not loaded), rector will happily rewrite `$this->assertTrue(...)` to `expect(...)` even when phpstan considers the result redundant - so write the chained `expect()` form with default args from the start. See https://getrector.com/documentation/config-configuration#content-phpstan-integration.

## Forbidden

- No bare `test()` calls
- No `Modules\*` imports in `tests/` outside the seam (except Seeder imports)
- No modifying ArchitectureTest.php without explicit approval

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
