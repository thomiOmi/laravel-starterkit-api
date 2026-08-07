---
status: findings
phase: 1-quality-foundation
files_reviewed:
  - bootstrap/app.php
  - modules/IAM/Tests/Feature/AuthRateLimitTest.php
  - phpstan.neon
  - tests/Unit/Http/Exceptions/ExceptionHandlerTest.php
date: 2026-08-07
---

## Findings

| ID | Severity | File | Line | Summary |
|----|----------|------|------|---------|
| WR-01 | WARNING | bootstrap/app.php | 151-162 | Generic HttpExceptionInterface renderer changed despite phase contract saying it "must remain unchanged (intentionally header-less)" — now forwards exception headers and swaps the title |
| WR-02 | WARNING | modules/IAM/Tests/Feature/AuthRateLimitTest.php | 52-53 | Success-flow assertions hardcode `X-RateLimit-Remaining = '4'`, coupled to default config and Laravel's header-merge guard |
| IN-01 | INFO | modules/IAM/Tests/Feature/AuthRateLimitTest.php | 28, 74, 113, 154, 188, 214, 249, 291 | Hardcoded `'Too Many Attempts.'` detail couples tests to Laravel's ThrottleRequestsException message; per-email 422/201 header assertions rely on framework header-merge guard |
| IN-02 | INFO | tests/Unit/Http/Exceptions/ExceptionHandlerTest.php | 140-177 | New tests encode generic-renderer header forwarding that contradicts the phase's stated contract (see WR-01) |

### WR-01: Generic HttpExceptionInterface renderer changed against the phase contract

- **File:** bootstrap/app.php
- **Line:** 151-162
- **Severity:** WARNING
- **Description:** The phase description states the generic `HttpExceptionInterface` renderer "must remain unchanged (intentionally header-less)" — only the 429 renderer should forward headers. The diff shows the opposite: the generic renderer gained `headers: $e->getHeaders()` (line 160) and its title changed from `__('auth.http_forbidden')` to `Response::$statusTexts[$e->getStatusCode()] ?? __('auth.access_denied')` (line 157). The title change is arguably a fix (the old code showed "Forbidden" for every generic status), but the header forwarding changes the HTTP response surface for every non-specific `HttpException` app-wide (maintenance-mode 503s, `abort(418)`, mapped 419s, etc.) — a behavior change that is not documented in the phase plan and contradicts the declared intent that only the 429 path carries headers. Because the new unit tests (IN-02) assert the new behavior, this looks deliberate rather than accidental, but it is still an unapproved scope deviation. If header forwarding in the generic renderer is intended, the phase documentation should say so; if not, revert line 160 and drop the tests.
- **Suggested fix:** Either remove `headers: $headers` from the generic renderer to restore the stated contract, or explicitly document/approve the behavior change. If kept, the title fallback should be a neutral string rather than `__('auth.access_denied')` when the status is not in `$statusTexts`.

### WR-02: Success-flow test assertions coupled to default config and framework merge guard

- **File:** modules/IAM/Tests/Feature/AuthRateLimitTest.php
- **Line:** 52-53
- **Severity:** WARNING
- **Description:** `$response->assertHeader('X-RateLimit-Remaining', '4')` is a hardcoded magic value that is only correct while `rate-limiting.auth.limit_per_email` defaults to 5. If `RATE_LIMIT_AUTH_PER_EMAIL` env changes (e.g., to 6), `X-RateLimit-Limit` (read from config on line 52) keeps passing while `X-RateLimit-Remaining` '4' fails with a confusing mismatch. The value also depends on Laravel's `ThrottleRequests::getHeaders()` guard: the second limit (per-IP, remaining 9) is skipped only because the existing remaining (4) is `<=` 9. If `rate-limiting.auth.limit_per_ip` were ever lowered below the email limit's remaining, the IP limit's headers would win and the assertion would fail. Unlike the other tests, this one does not pin the config, so it validates an environment-dependent value.
- **Suggested fix:** Pin the config in the test and derive the expectation, e.g.:
  ```php
  config()->set('rate-limiting.auth.limit_per_email', 5);
  config()->set('rate-limiting.auth.limit_per_ip', 10);

  $response->assertHeader('X-RateLimit-Limit', '5');
  $response->assertHeader('X-RateLimit-Remaining', '4');
  ```
  or compute `(string) (config()->integer('rate-limiting.auth.limit_per_email') - 1)`.

### IN-01: Framework-internal strings and merge-guard behavior baked into assertions

- **File:** modules/IAM/Tests/Feature/AuthRateLimitTest.php
- **Line:** 28, 74, 113, 154, 188, 214, 249, 291
- **Severity:** INFO
- **Description:** All 8 429 tests assert `expect($response->json('detail'))->toBe('Too Many Attempts.')` — the literal default message of `Illuminate\Http\Exceptions\ThrottleRequestsException`. If Laravel ever rewords that message, every test here breaks for no functional reason. Similarly, the per-email tests assert `X-RateLimit-Limit: 2` on non-429 responses (201/422) whose header value survives only because of the framework's header-merge guard (first limit's remaining `<=` second limit's remaining skips the overwrite). This is framework-version-sensitive behavior being asserted as a contract.
- **Suggested fix:** Assert `detail` is non-empty (`expect($response->json('detail'))->not->toBeEmpty()`) instead of the exact framework message, and add a comment in the per-email tests noting the reliance on the header-merge guard.

### IN-02: Unit tests encode the generic-renderer deviation

- **File:** tests/Unit/Http/Exceptions/ExceptionHandlerTest.php
- **Line:** 140-177
- **Severity:** INFO
- **Description:** The three new tests (`forwards custom string headers`, `stringifies integer header values`, `forwards multi-value headers`) assert that the generic `HttpExceptionInterface` renderer forwards headers — behavior the phase explicitly says should not exist ("intentionally header-less"). They also only cover `HttpException` (the base class); the union-matched subtypes in `bootstrap/app.php` (e.g., `ServiceUnavailableHttpException` via the generic path) behave identically but are untested. These tests lock in the WR-01 deviation, so they must be updated or removed together with the renderer decision.
- **Suggested fix:** If the generic renderer is reverted per contract, delete these three tests; if the deviation is approved, keep them but document the decision in the phase summary.

## Verification performed

- Read all 4 files in scope plus call-context: `app/Http/Responses/ProblemResponse.php`, `config/rate-limiting.php`, `config/errors.php`, `modules/IAM/Routes/V1.php`, `app/Providers/AppServiceProvider.php` (auth limiter), `tests/Pest.php`, `tests/Helpers.php`, `phpunit.xml`, and the installed `Illuminate\Foundation\Exceptions\Handler`, `ThrottleRequests`, `Symfony\HttpException`, `TooManyRequestsHttpException`, and `HeaderBag` implementations.
- Ran `vendor/bin/pest` on the new module suite (9 tests, 199 assertions) and the unit suite (16 tests, 134 assertions): all pass. Full suite: 285 tests, 1019 assertions, all pass.
- Ran `vendor/bin/phpstan analyse modules/IAM/Tests` (post exclude-removal): 0 errors. `vendor/bin/pint --test` on the changed files: clean.
- Probed actual runtime headers via a temporary test (removed after): success login returns `X-RateLimit-Limit: 5, X-RateLimit-Remaining: 4`; throttled login returns `X-RateLimit-Limit: 2, X-RateLimit-Remaining: 0, Retry-After: 60, X-RateLimit-Reset: <epoch>`, confirming the 429 header forwarding contract works end to end.

No BLOCKER findings: the 429 header-forwarding contract is implemented correctly (verified against the framework's `ThrottleRequests::buildException` int-typed headers and `ProblemResponse::normalizeHeaders` stringification), the `phpstan.neon` change is exactly as described, and all quality gates pass.

---

_Reviewed: 2026-08-07_
_Reviewer: gsd-code-reviewer_
_Depth: standard_
