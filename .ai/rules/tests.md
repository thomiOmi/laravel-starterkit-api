---
paths:
  - 'tests/**, modules/*/Tests/**'
---

# Tests

## Pest test structure, helpers, and placement
Every test file MUST use describe() blocks and it() (never bare test()); name describes behavior, not implementation. Tag with ->group('module:{module}') and smoke/slow/integration sparingly. Use typed helpers from tests/Helpers.php (assertSuccessResponse, assertProblemResponse, assertPaginatedResponse, loginAsUser, loginAsRole, responseData, artisanCommand) instead of inline getData()/artisan(). App-layer tests (tests/) may only import the module User model/factory through the Helpers seam, never Modules\* imports directly; module tests stay self-contained in modules/*/Tests/. RefreshDatabase for feature tests; beforeEach seeds roles, forgetCachedPermissions(), creates admin via loginAsUser. Reusable helpers go to tests/Helpers.php (3+ files), named datasets to tests/Datasets/{Name}.php (2+ uses). Full reference: docs/testing.md.
