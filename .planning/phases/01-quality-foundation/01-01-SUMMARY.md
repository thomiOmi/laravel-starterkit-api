---
phase: 1-quality-foundation
plan: 01
subsystem: api
tags: [laravel, pest, phpstan, tdd, rate-limiting, sanctum, rfc-9457]

requires: []
provides:
  - "9-test auth rate-limit contract suite covering login, register, forgot-password, reset-password with per-email and per-IP scenarios plus one success flow"
  - "429 ProblemResponse now renders the throttle middleware's computed headers (X-RateLimit-Limit, X-RateLimit-Remaining, Retry-After, X-RateLimit-Reset)"
  - "Retry-After regression guard in the exception-handler unit suite"
affects:
  - "01-02 (phpstan gates and type-coverage enforcement on module tests)"
  - "modules/IAM auth work (contract nailed down for future route changes)"

actuals:
  tokens: 3700
  tasks: 3
  commits: 3

tech-stack:
  added: []
  patterns:
    - "Throttle headers forwarded from the Symfony exception into the ProblemResponse contract via a string=>string normalization at the framework boundary"

key-files:
  created:
    - modules/IAM/Tests/Feature/AuthRateLimitTest.php
  modified:
    - bootstrap/app.php
    - tests/Unit/Http/Exceptions/ExceptionHandlerTest.php

key-decisions:
  - "Forward only string=>string throttle header entries (stringifying int values) instead of the plan's literal $e->getHeaders() so PHPStan at max level accepts the ProblemResponse contract"
  - "Throttle header values arrive as ints from ThrottleRequests and must be stringified at the boundary, verified by the contract suite"

requirements-completed: [API-01, API-02, API-03]

coverage:
  - id: D1
    description: "429 responses on all four throttle:auth routes return a full RFC 9457 ProblemResponse via assertProblemResponse plus X-RateLimit-Limit, X-RateLimit-Remaining, Retry-After and X-RateLimit-Reset"
    requirement: API-02
    verification:
      - kind: integration
        ref: "modules/IAM/Tests/Feature/AuthRateLimitTest.php#describe('login rate limit') / describe('register rate limit') / describe('forgot-password rate limit') / describe('reset-password rate limit')"
        status: pass
    human_judgment: false
  - id: D2
    description: "Successful login returns the SuccessResponse envelope (assertSuccessResponse) with X-RateLimit-Limit and X-RateLimit-Remaining matching config()->integer() values"
    requirement: API-02
    verification:
      - kind: integration
        ref: "modules/IAM/Tests/Feature/AuthRateLimitTest.php#login success flow returns SuccessResponse envelope with rate limit headers"
        status: pass
    human_judgment: false
  - id: D3
    description: "bootstrap/app.php 429 render closure forwards the throttle middleware's computed headers into the ProblemResponse; Retry-After regression guard on the generic renderer"
    requirement: API-02
    verification:
      - kind: integration
        ref: "modules/IAM/Tests/Feature/AuthRateLimitTest.php#429 responses carry X-RateLimit-* and Retry-After headers"
        status: pass
      - kind: unit
        ref: "tests/Unit/Http/Exceptions/ExceptionHandlerTest.php#renders 429 rate_limited problem response with Retry-After"
        status: pass
    human_judgment: false

duration: 25m
completed: 2026-08-03
status: complete
---

# Phase 1 Quality Foundation: Plan 01 Summary

**RFC 9457 rate-limiting contract now verified end to end on all four auth routes: 429 responses carry X-RateLimit-Limit, X-RateLimit-Remaining, Retry-After and X-RateLimit-Reset, shipped via a RED-to-GREEN fix that forwards the throttle middleware's headers into the ProblemResponse.**

## Performance

- **Duration:** ~25m
- **Started:** 2026-08-03T22:40:31Z
- **Completed:** 2026-08-03
- **Tasks:** 3
- **Files modified:** 3

## Accomplishments

- 9-test contract suite `modules/IAM/Tests/Feature/AuthRateLimitTest.php` (199 assertions) covering login, register, forgot-password and reset-password with per-email (limit 2, third request 429) and per-IP scenarios, plus a success-flow test asserting the SuccessResponse envelope with configured rate-limit headers.
- Proved the defect first: in the RED phase, 8 of 9 tests failed solely on missing rate-limit headers while every status, body and detail assertion passed, pinning the bug to the 429 renderer in `bootstrap/app.php`.
- GREEN fix: the 429 render closure now forwards the exception's computed headers into the ProblemResponse; a Retry-After assertion in the exception-handler unit suite locks the regression.
- N+1 boundary verified: under a limit of 2 the first two requests are non-429 and the third is 429.

## Task Commits

Each task was committed atomically:

1. **Task 1: add failing rate-limit contract tests for login** - `b544535` (test / RED)
2. **Task 2: extend rate-limit contract to all auth routes and per-IP** - `092ea4c` (test / RED)
3. **Task 3: forward throttle headers into 429 problem response** - `9366ac4` (feat / GREEN)

**Plan metadata:** pending final docs commit (SUMMARY, STATE, ROADMAP, REQUIREMENTS).

## Files Created/Modified

- `modules/IAM/Tests/Feature/AuthRateLimitTest.php` - new 9-test contract suite (strict_types, no uses(), RefreshDatabase via module Pest binding, full type coverage).
- `bootstrap/app.php` - 429 render closure normalizes and forwards `$e->getHeaders()` into the ProblemResponse.
- `tests/Unit/Http/Exceptions/ExceptionHandlerTest.php` - 429 block gained `assertHeader('Retry-After', '60')`.

## Decisions Made

- Normalize throttle headers at the framework boundary (keep string keys, stringify int values) rather than passing Symfony's unshaped `array` straight into the `array<string, string>` ProblemResponse contract. This keeps `composer types:check` green at max level without ignores, baselines, casts-to-silence or widening.
- Keep the full scenario set (per-email AND per-IP on all four routes) in one file as the plan's two-step RED split, rather than collapsing to a single minimal case.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Plan's literal `headers: $e->getHeaders(),` fails PHPStan at max level**
- **Found during:** Task 3 (GREEN)
- **Issue:** Symfony's `HttpExceptionInterface::getHeaders(): array` is unshaped; `ProblemResponse` requires `array<string, string>`. The plan's exact line produces error `argument.type` (Parameter $headers expects array<string, string>, array given) in `bootstrap/app.php`, breaking the phase's own quality gate.
- **Fix:** Loop the framework headers and keep only string-keyed entries, stringifying int values - an identical-behavior normalization that PHPStan max-level accepts.
- **Files modified:** `bootstrap/app.php`
- **Verification:** `composer types:check` 0 errors; feature suite 9/9 (199 assertions); unit suite 13/13 (129 assertions).
- **Committed in:** `9366ac4`

**2. [1 - Bug] First normalization iteration dropped integer-valued headers**
- **Found during:** Task 3 (GREEN, pre-commit)
- **Issue:** ThrottleRequests stores e.g. `X-RateLimit-Limit` as `int`, so an `is_string($value)` filter silently discarded every header, regressing to the exact RED the suite was built to catch (8/9 failures).
- **Fix:** Accept `is_string($value) || is_int($value)` and stringify with `strval($value)`.
- **Files modified:** `bootstrap/app.php`
- **Verification:** Feature suite back to 9/9 green (199 assertions) - the contract tests caught the implementation bug before commit.
- **Committed in:** `9366ac4`

---

**Total deviations:** 2 auto-fixed (1 blocking behind the type gate, 1 bug caught by the fresh suite).
**Impact on plan:** Both were correctness/type-gate necessities inside the task the plan itself defined; no scope creep, no architectural change.

## Issues Encountered

- PHPStan infers `array_filter` narrowing from declared closure parameter types, so "narrow the filter callback" variants (`fn (string $key, string $value): bool => true`) are rejected as callable contract violations, and `strval`/casts on `mixed` are refused. A plain `foreach` with `is_string`/`is_int` guards lets PHPStan's flow analysis produce the typed array. (Relevant if a later plan re-touches framework-typed boundary arrays.)
- Clock anomaly during the phase (plan start recorded as 22:40Z, wall clock reading ~16:00Z); duration recorded grossly (~25m actual work) - no impact on artifacts.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Baseline contract suite green; the phpstan.neon `modules/*/Tests/*` exclusion (deferred to Plan 02) can now be removed and max-level type checks enabled against a suite that already satisfies max-level styling.
- The throttle-header design (stringified ints) is now the boundary pattern to keep.

---

*Phase: 1-quality-foundation*
*Completed: 2026-08-03*