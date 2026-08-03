# Walking Skeleton - Laravel Starterkit API

**Phase:** 1 (Quality Foundation)
**Generated:** 2026-08-03

## Capability Proven End-to-End

A client of the auth API receives the complete rate-limit contract through the real stack: non-429 responses carry X-RateLimit-Limit / X-RateLimit-Remaining, and a throttled request returns the RFC 9457-style ProblemResponse body plus Retry-After and X-RateLimit-Reset. The whole path (route, throttle middleware, named auth limiter, exception pipeline, custom ProblemResponse renderer) is exercised by feature tests and enforced by PHPStan at max level with 100 percent type coverage.

## Architectural Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Framework | Laravel 13 (PHP 8.4, pinned composer toolchain) | Project foundation; all ecosystem packages (Sanctum 4, Socialite 5, Pennant 1, Pest 5) already pinned in composer.json |
| Data layer | MySQL (prod, via FlyEnv) / SQLite :memory: (tests, phpunit.xml) | Fast green suite; no external service needed to run tests |
| Auth | Laravel Sanctum personal access tokens (Bearer), ULID-only IDs | Per-device token revocation; JWT explicitly rejected in REQUIREMENTS out-of-scope |
| Rate limiting | Named limiters (auth / api / authenticated) in app/Providers/AppServiceProvider, config in config/rate-limiting.php, applied per route via throttle middleware | Per-email (5/min) + per-IP (10/min) keying for auth endpoints; lazy config read so tests override via config()->set() |
| API response contract | Custom SuccessResponse / ProblemResponse (RFC 9457-style) with type keys, no OpenAPI contract tests; Scramble docs only | Custom envelope is the shipped contract; verified end-to-end this phase |
| Directory layout | Modular DDD: modules/* with Actions, Controllers, Requests, Resources, Routes, Tests | Module isolation via contracts; module tests live under modules/*/Tests/Feature (phpunit "Modules" testsuite + tests/Pest.php bindings) |
| Quality gates | PHPStan level max (larastan + pest-plugin-phpstan), type coverage 100 percent, Pint, Pest feature/unit tests; gates wired through composer scripts only (test:quality, ci:check), no hooks | Failing-fast baseline shipped and verified in Phase 1; enforcement stays in the existing scripts (D-08) |

## Stack Touched in Phase 1

- [x] Project scaffold - Laravel 13 application already scaffolded; no new packages (Package Legitimacy Audit N/A)
- [x] Routing - the four real auth routes exercised end-to-end (POST /api/v1/auth/login|register|forgot-password|reset-password, all throttle:auth)
- [x] Database - test database read/write through UserFactory in the success-flow test (SQLite :memory:)
- [x] UI - not applicable: this is an API starterkit; the interactive element is an API client request, exercised via real-route feature tests (assertSuccessResponse / assertProblemResponse helpers)
- [x] Deployment - documented full-stack verification command composer ci:check runs the complete gate (lint + phpstan max + type coverage 100 + rector + profanity + whole Pest suite); this API ships via the existing CI pipeline (tests.yml)

## Out of Scope (Deferred to Later Slices)

- Rate-limit verification for the throttle:api and throttle:authenticated limiter classes (Phase 2/3 - Authentication, Social Auth)
- Full 429 retry-flow travel test (considered, not chosen - runtime without proportional value)
- Account lifecycle endpoints themselves (register/login flows are built; verification of full AUTH-01..08 happens in Phase 2)
- OpenAPI contract schema tests, JWT, UUID/int keys, IP/user-agent encryption, repositories, entity providers (REQUIREMENTS out-of-scope table)

## Subsequent Slice Plan

- Phase 2: complete account lifecycle (register, login, tokens, verify, reset, password, delete), all rate-limited with throttle headers - using the AuthRateLimitTest pattern and the fixed 429 renderer
- Phase 3: social auth plus profile (Google/GitHub OAuth, link/unlink, profile CRUD)
- Phase 4: IAM admin (users/roles/permissions CRUD + assignment)
- Phase 5: feature flags (Pennant)
- Phase 6: idempotency keys + Scramble OpenAPI docs
- Phase 7: observability (health endpoint, Pulse)
- Phase 8: modern features + advanced testing