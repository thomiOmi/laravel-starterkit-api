# Phase 1: Quality Foundation - Pattern Map

**Mapped:** 2026-08-03
**Files analyzed:** 4 (1 new, 3 modified)
**Analogs found:** 4 / 4

## File Classification

| New/Modified File | Role | Data Flow | Closest Analog | Match Quality |
|-------------------|------|-----------|----------------|---------------|
| `modules/IAM/Tests/Feature/AuthRateLimitTest.php` (NEW) | test (feature, contract) | request-response (real routes) | `tests/Feature/Http/Requests/BulkActionRequestTest.php` | exact (POST + assert helpers) |
| `bootstrap/app.php` (MODIFIED - 429 render closure) | config/bootstrap (exception rendering) | request-response | `bootstrap/app.php` lines 131-138 (the closure itself) + `app/Http/Responses/ProblemResponse.php` lines 29-37, 71-73 | exact |
| `phpstan.neon` (MODIFIED - excludePaths) | config (static analysis) | N/A | `phpstan.neon` lines 14-15 (the file itself) | exact |
| `tests/Unit/Http/Exceptions/ExceptionHandlerTest.php` (OPTIONAL MODIFIED - 429 case) | test (unit, render) | request-response (exception render) | `tests/Unit/Http/Exceptions/ExceptionHandlerTest.php` lines 104-114 (the rate-limit describe block) | exact |

**Note:** `modules/IAM/Tests/` is currently empty (verified) — no module test analog exists. Root `tests/` conventions are the source of truth, and `tests/Pest.php` already binds them to module paths.

## Pattern Assignments

### `modules/IAM/Tests/Feature/AuthRateLimitTest.php` (test, request-response)

**Analog 1 (POST-route feature test):** `tests/Feature/Http/Requests/BulkActionRequestTest.php`
**Analog 2 (real-route ProblemResponse assertions):** `tests/Feature/Http/Middleware/GlobalApiMiddlewareTest.php`
**Analog 3 (assertion helpers):** `tests/Helpers.php`

**File header + structure pattern** (`BulkActionRequestTest.php` lines 1-13, `GlobalApiMiddlewareTest.php` lines 1-10):
```php
<?php

declare(strict_types=1);

use Modules\IAM\Database\Factories\UserFactory;

describe('login rate limit', function () {
    describe('per-email', function () {
        it('returns 429 with problem response when per-email limit is exceeded', function () {
            // ...
        })->group('smoke');
    });
});
```

**Key conventions extracted:**
- `declare(strict_types=1)` on line 3 — MANDATORY: type coverage gate (`--type-coverage --min=100`) scans module test files and does NOT apply phpunit.xml excludes (verified in RESEARCH Pitfall 5).
- NO `uses(RefreshDatabase::class)` — `tests/Pest.php` lines 20-23 already bind `TestCase` + `RefreshDatabase` for `'Feature', '../modules/*/Tests/Feature'`. Adding it triggers phpstan `pest.config.redundantLocalUse` (RESEARCH Pitfall 4).
- NO `namespace` declaration needed (RESEARCH assumption A4) — discovery is path-based.
- `covers()` is NOT used in `GlobalApiMiddlewareTest.php` (no single class under test — same applies to the new file; do NOT add `covers()`).

**Config-override + N+1 requests pattern** (`ExceptionHandlerTest.php` lines 143-153 for `config()->set()` style; D-04):
```php
config()->set('rate-limiting.auth.limit_per_email', 2);
config()->set('rate-limiting.auth.limit_per_ip', 100);

$this->postJson('/api/v1/auth/login', ['email' => 'limit@example.com', 'password' => 'wrong'])->assertStatus(422);
$this->postJson('/api/v1/auth/login', ['email' => 'limit@example.com', 'password' => 'wrong'])->assertStatus(422);

$response = $this->postJson('/api/v1/auth/login', ['email' => 'limit@example.com', 'password' => 'wrong']);
```

**Response envelope assertion pattern** (`GlobalApiMiddlewareTest.php` lines 12-22, helpers at `tests/Helpers.php` lines 119-164):
```php
assertProblemResponse($response, 429, 'rate-limit-exceeded');
expect($response->json('detail'))->toBe('Too Many Attempts.')   // framework literal, NOT the translation
    ->and($response->headers->get('X-RateLimit-Limit'))->toBe('2')
    ->and($response->headers->get('X-RateLimit-Remaining'))->toBe('0')
    ->and($response->headers->get('Retry-After'))->toBeGreaterThanOrEqual(1)
    ->and((int) $response->headers->get('X-RateLimit-Reset'))->toBeGreaterThan(time());
```
Success flow (D-07) uses `assertSuccessResponse($response, 200, 'OK')` + `toHaveKeys(['user', 'access_token', 'token_type', 'expires_at', 'expires_in'])` on `$response->json('data')` (helper at `tests/Helpers.php` lines 146-164).

**Route table for the 4 covered routes** (`modules/IAM/Routes/V1.php` lines 41-44, all `throttle:auth`):
| Route | Payload |
|---|---|
| `POST /api/v1/auth/login` | `{email, password}` |
| `POST /api/v1/auth/register` | `{name, email, password, password_confirmation}` |
| `POST /api/v1/auth/forgot-password` | `{email}` |
| `POST /api/v1/auth/reset-password` | `{token, email, password, password_confirmation}` |

(Password rules include `confirmed` — register/reset need `password_confirmation` or they 422 for the wrong reason; RESEARCH Pitfall 6.)

**Typed config read for asserting actual limits (D-02)** — `config()->integer('rate-limiting.auth.limit_per_email')` (defaults: 5/10 per `config/rate-limiting.php` lines 31-35).

---

### `bootstrap/app.php` (config/bootstrap, request-response)

**Analog:** the 429 render closure itself, `bootstrap/app.php` lines 130-138, plus `ProblemResponse` headers support at `app/Http/Responses/ProblemResponse.php`.

**Current closure (lines 131-138) — the ONLY change is adding one line:**
```php
$exceptions->render(function (TooManyRequestsHttpException $e, Request $request): ProblemResponse {
    return new ProblemResponse(
        typeKey: 'rate_limited',
        title: __('auth.http_too_many_requests'),
        status: $e->getCode() !== 0 ? $e->getCode() : Response::HTTP_TOO_MANY_REQUESTS,
        detail: $e->getMessage() !== '' ? $e->getMessage() : __('auth.rate_limited_detail'),
        headers: $e->getHeaders(),  // <-- ADD THIS LINE
    );
});
```

**Supporting evidence (do not change):**
- `ProblemResponse` 7th constructor param `private array $headers = []` (`ProblemResponse.php` line 36) and merge in `toResponse()` (`array_merge($this->headers, ['Content-Type' => ...])`, lines 71-73).
- Sibling closures (422 at lines 84-94, 401 at 97-104, 403 at 107-118, 404 at 121-128, 400 at 141-148) share the same `typeKey/title/status/detail` named-args shape — copy the shape, add only the `headers` line to the 429 closure.
- Generic `HttpExceptionInterface` closure (lines 151-158) also drops headers — INTENTIONAL non-change (RESEARCH Open Question 1); do not touch.

---

### `phpstan.neon` (config, static analysis)

**Analog:** the file itself, lines 14-15.

**The change (D-11, D-12) — remove one line:**
```diff
     excludePaths:
-        - modules/*/Tests/*
```
`tests` stays in `paths` (line 12) and is NOT touched. No level/parameter changes — level max (line 17) and larastan rules (lines 22-28) stay as-is.

---

### `tests/Unit/Http/Exceptions/ExceptionHandlerTest.php` (test, request-response render)

**Analog:** the file itself — rate-limit describe block, lines 104-114, and the `renderApiException` helper, lines 20-32.

**Current 429 case (lines 106-112) — optional addition of ONE header assertion:**
```php
it('renders 429 rate_limited problem response', function () {
    $response = renderApiException(new TooManyRequestsHttpException(60));

    assertProblemResponse($response, Response::HTTP_TOO_MANY_REQUESTS, 'rate-limit-exceeded');

    expect($response->json('detail'))->toBe(__('auth.rate_limited_detail'));
})->group('smoke');
```
After the bootstrap/app.php fix, `new TooManyRequestsHttpException(60)` renders with `Retry-After: '60'` — add `$response->assertHeader('Retry-After', '60')` (Symfony sets Retry-After from the retryAfter arg). Note the unit test asserts the TRANSLATED detail (`__('auth.rate_limited_detail')`) because the exception message is empty here — do not copy that assertion into the feature test (real middleware messages are the literal `'Too Many Attempts.'`; RESEARCH Pitfall 2).

## Shared Patterns

### Pest File Conventions (ALL test files)
**Sources:** `tests/Feature/Http/Middleware/GlobalApiMiddlewareTest.php` lines 1-10, `tests/Unit/Http/Exceptions/ExceptionHandlerTest.php` lines 1-18, AGENTS.md testing rules
- `declare(strict_types=1)` first line — enforced by type-coverage gate
- `describe()` per unit/concern, nested `describe()` for sub-scenarios; `it()` for every test (never bare `test()`)
- `->group('smoke')` for critical-path tests (429 contract IS critical-path); `->group('module:iam')` used in `BulkActionRequestTest.php` line 41 for module grouping
- No `uses()` call — bindings come from `tests/Pest.php` (Feature + `../modules/*/Tests/Feature` get `TestCase` + `RefreshDatabase`)
- No `dump()`/`var_dump()` — `phpunit.xml` sets `beStrictAboutOutputDuringTests="true"` + `failOnRisky="true"`
- Fully typed closure params — type coverage counts FunctionLike params (zero-param closures are exempt)

### Response Envelope Assertions
**Source:** `tests/Helpers.php` lines 119-196 (auto-loaded globally by Pest, usable in module tests)
- `assertProblemResponse($response, $status, $type)` — asserts `application/problem+json` + RFC 9457 structure (`type, title, status, detail, timestamp`) + optional type contains-match
- `assertSuccessResponse($response, $status, $title)` — asserts `{status, title, detail, data}` for 2xx
- `responseData($response)` — decodes JSON body to array (used in unit tests)
- Custom expect() extensions are NOT used for response assertions — `tests/Expectations.php` lines 16-18 documents that PHPStan only understands typed helper functions (keep new assertions in helper functions, not `expect()->extend()`)

### Test Config Overrides
**Source:** `tests/Unit/Http/Exceptions/ExceptionHandlerTest.php` lines 143/153, `tests/Unit/Http/Responses/ProblemResponseTest.php` lines 11-15
- `config()->set('app.debug', false)` style overrides are the established pattern; the new file uses `config()->set('rate-limiting.auth.limit_per_email', 2)` (D-04)
- Typed reads: `config()->integer(...)`, `config()->string(...)`, `config()->boolean(...)` per AGENTS.md

### Exception Render Closure Shape (bootstrap/app.php)
**Source:** `bootstrap/app.php` lines 78-183
- All closures: `$exceptions->render(function (SpecificException $e, Request $request): ProblemResponse { ... })` returning `new ProblemResponse(typeKey: ..., title: ..., status: ..., detail: ..., [extensions: ..., headers: ...])`
- `status` pattern: `$e->getCode() !== 0 ? $e->getCode() : Response::HTTP_*`
- `detail` pattern: `$e->getMessage() !== '' ? $e->getMessage() : __('auth.*_detail')`
- The `respond()` closure (lines 175-183) re-applies Trace/Security headers — it does NOT merge exception headers, hence the explicit `headers:` line needed

## No Analog Found

| File | Role | Data Flow | Reason |
|------|------|-----------|--------|
| (none) | — | — | All 4 files have exact in-repo analogs; module test dir is empty but root `tests/` conventions + `tests/Pest.php` bindings fully cover the new file |

## Metadata

**Analog search scope:** `tests/` (Feature, Unit, Pest.php, Helpers.php, Expectations.php), `bootstrap/app.php`, `phpstan.neon`, `phpunit.xml`, `config/rate-limiting.php`, `modules/IAM/Routes/V1.php`, `modules/IAM/Database/Factories/`, `app/Http/Responses/ProblemResponse.php`
**Files scanned:** 34 (test files) + 8 (app/config/module files)
**Pattern extraction date:** 2026-08-03
