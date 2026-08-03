# Phase 1: Quality Foundation - Context

**Gathered:** 2026-08-03
**Status:** Ready for planning

<domain>
## Phase Boundary

Verify and lock the quality baseline of the shipped starterkit: PHPStan at max level with zero errors, 100% type coverage, and the API contract (SuccessResponse/ProblemResponse RFC 9457 + rate-limit headers) verified end-to-end through real routes. The baseline itself is already green; the phase closes the verification gap (rate-limit contract tests) and extends PHPStan coverage to module tests.

</domain>

<decisions>
## Implementation Decisions

### Rate-Limit Verification Scope
- **D-01:** Cover all `throttle:auth` routes — login, register, forgot-password, reset-password — in the new contract tests. Per-limiter class scope (api/authenticated) is out of scope; those routes belong to later phases. — **Reversibility:** reversible — adding routes to the test file later is cheap
- **D-02:** On 200 responses assert `X-RateLimit-Limit` and `X-RateLimit-Remaining` with the actual config values (`rate-limiting.auth.limit_per_email` / `limit_per_ip`). `X-RateLimit-Reset` is NOT asserted on 200 — the framework only sends it (plus `Retry-After`) on 429 responses (verified in `vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php` `getHeaders()`). — **Reversibility:** reversible
- **D-03:** On 429 assert full `ProblemResponse` body (status, title, detail) plus `Retry-After` and `X-RateLimit-Reset` headers. Two separate scenarios: per-email limit exceeded and per-IP limit exceeded. — **Reversibility:** reversible
- **D-04:** Simulate limit exhaustion via `config()->set()` overriding `rate-limiting.auth.limit_per_*` to small values (e.g., 2), then send N+1 requests. Do NOT send the real 5/10 requests — slow and env-dependent. — **Reversibility:** reversible

### Contract Test Location & Shape
- **D-05:** Keep the existing structure — no new `tests/Feature/Contract/` suite. Unit tests for response shape stay in `tests/Unit/Http/Responses/` and are left unchanged.
- **D-06:** New feature tests live in `modules/IAM/Tests/Feature/AuthRateLimitTest.php` — a single file with `describe()` per route (login, register, forgot-password, reset-password) and per-email/per-IP scenarios as separate `describe()` blocks. Infrastructure already exists: `tests/Pest.php` binds `TestCase` + `RefreshDatabase` for `../modules/*/Tests/Feature`, phpunit.xml has the "Modules" testsuite, helpers (`assertProblemResponse`, etc.) are auto-loaded by Pest.
- **D-07:** In addition to rate-limit tests, add ONE success-flow test asserting `SuccessResponse` shape via a real route (login success → `{status, data}` + rate-limit headers). This closes the API-02 end-to-end verification gap. Validation-error (422) cases are NOT added here — already covered elsewhere. — **Reversibility:** reversible

### Failing-Fast Enforcement
- **D-08:** No new enforcement mechanisms (no pre-commit hook, no `quality:check` command, no arch rules). `composer test:quality` (manual) + CI `tests.yml` running `composer ci:check` are sufficient. — **Reversibility:** reversible
- **D-09:** Phase is complete when: all gates green (PHPStan 0 errors at max, type coverage 100%, suite passes) AND the new rate-limit feature tests pass. — **Reversibility:** reversible
- **D-10:** Any unexpected findings surfaced by the new verification tests are fixed IN THIS PHASE — the contract is the source of truth. No deferring.

### PHPStan Scope
- **D-11:** Remove `modules/*/Tests/*` from `excludePaths` in `phpstan.neon` so module test files are analyzed at max level. This strengthens the gate (consistent with AGENTS.md's prohibition on weakening config — the ban targets lowering level/adding ignores, not removing excludes). `modules/IAM/Tests/` is currently empty, so no immediate errors; the effect lands as module tests are written (this phase and Phase 2 Authentication).
- **D-12:** The `tests` path in `phpstan.neon` (root `tests/` directory) stays as-is — only the module exclude is removed.

### Phase Positioning
- **D-13:** This phase is verification + gap closure, not new feature building. Baseline (QLTY-01/02) already green: `composer types:check` exits 0 at max level, `composer test:quality` reports 100% type coverage, full suite 269 tests passing. All findings are resolved in this phase; nothing is deferred.

### the agent's Discretion
- Test naming conventions, `describe()` grouping granularity, and exact assertion helpers usage are left to the planner/executor following project Pest conventions (`it()` + `describe()`, `toBeSuccessResponse()`/`toBeProblemResponse()` helpers, `config()->string()` config access).

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Quality Gates & Tooling
- `phpstan.neon` — Config to modify: remove `modules/*/Tests/*` from `excludePaths` (D-11). Level max, bleedingEdge, larastan rules already configured.
- `composer.json` §scripts — `test:quality` (lint:check + types:check + pest `--coverage --type-coverage --min=100`), `ci:check` (test:quality + rector + profanity), `test:tia`. Do not modify.

### Test Infrastructure & Conventions
- `tests/Pest.php` — Binds `TestCase` + `RefreshDatabase` for Feature + `../modules/*/Tests/Feature`; module test discovery.
- `phpunit.xml` — Testsuitess: Architecture, Unit, Feature, Modules (`modules/*/Tests`); source dirs include `app` + `modules`; excludes module Tests/Database/Routes from coverage.
- `tests/Helpers.php` / `tests/Expectations.php` — Global helpers (`assertSuccessResponse`, `assertProblemResponse`, `assertPaginatedResponse`, `assertHasTraceId`, `responseData`, `loginAsUser`) auto-loaded by Pest; usable in module tests.
- `modules/IAM/Tests/` — Target directory for the new feature test file (currently empty).

### API Contract
- `modules/IAM/Routes/V1.php` — Route definitions with `throttle:auth` / `throttle:api` / `throttle:authenticated` middleware; names `v1.auth.login` etc.
- `app/Providers/AppServiceProvider.php` §configureRateLimiting — RateLimiter definitions (api/auth/authenticated) reading `rate-limiting` config.
- `config/rate-limiting.php` — Defaults: api=60/min, auth limit_per_email=5, limit_per_ip=10, authenticated (verify during planning).
- `app/Http/Responses/SuccessResponse.php` + `ProblemResponse.php` — The RFC 9457-shaped contract being verified.
- `tests/Unit/Http/Responses/SuccessResponseTest.php` + `ProblemResponseTest.php` — Existing unit tests (left unchanged, D-05).

### Laravel Behavior (verified during discussion)
- `vendor/laravel/framework/src/Illuminate/Routing/Middleware/ThrottleRequests.php` §getHeaders — Sends only `X-RateLimit-Limit`/`X-RateLimit-Remaining` on 200; adds `Retry-After` + `X-RateLimit-Reset` only when `$retryAfter` is non-null (429). Basis for D-02/D-03.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `assertProblemResponse($response, status, code)` helper — assert 429 ProblemResponse shape in feature tests
- `assertSuccessResponse()` / `responseData()` helpers — assert SuccessResponse shape and extract body
- `loginAsUser()` helper + seeded roles in `beforeEach` (per AGENTS.md testing rules)
- `config()->integer('rate-limiting.auth.limit_per_email')` — typed config access pattern for asserting actual limit values

### Established Patterns
- `describe()`/`it()` organization in Pest files (mandatory per AGENTS.md)
- Feature tests bind `TestCase` + `RefreshDatabase` automatically via `tests/Pest.php`
- Tests organized per concern; module tests under `modules/*/Tests/Feature/`

### Integration Points
- New test file: `modules/IAM/Tests/Feature/AuthRateLimitTest.php`
- Modified file: `phpstan.neon` (remove one exclude line)
- No production code changes expected — unless verification tests surface a contract violation (then fix in-phase per D-10)

</code_context>

<specifics>
## Specific Ideas

- During Postman testing the user noticed only `X-RateLimit-Limit` and `X-RateLimit-Remaining` on 200 responses — this triggered the verification of framework behavior and shaped D-02/D-03 (Reset/Retry-After only on 429).
- User explicitly prefers module tests living in `modules/IAM/Tests/Feature/` over `tests/Feature/` — will carry into Phase 2 (Authentication) test conventions.

</specifics>

<deferred>
## Deferred Ideas

- Rate-limit verification for `throttle:api` (email-verification, social routes, users/roles) and `throttle:authenticated` limiter classes — belongs to Phase 2/3 (Authentication, Social Auth) where those routes are built/verified.
- Full 429 retry-flow test (travel 60s + assert recovery) — considered, not chosen; adds runtime without proportional value.
- PHPStan analysis for module tests may surface type errors in factory files as tests get written — not deferred, will be resolved in-phase as they arise.

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 1-Quality Foundation*
*Context gathered: 2026-08-03*
