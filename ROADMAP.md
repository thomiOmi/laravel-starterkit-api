# Project Roadmap: Laravel StarterKit API

**Status**: Active  
**Goal**: Industrial-ready Laravel starterkit with modular architecture, first-party package optimization, and comprehensive test coverage.

---

## Phase Overview

| Phase | Title | Focus Area | Priority | Status |
|:---|:---|:---|:---|:---|
| **01** | Foundation & Code Quality | PHPStan, security checks, headers | 🔴 HIGH | [ ] |
| **02** | First-Party Package Audits | Socialite, Pennant, Spatie, Auth | 🔴 HIGH | [ ] |
| **03** | API Hardening | Rate limits, Idempotency, Health | 🔴 HIGH | [ ] |
| **04** | Test Modernization | Rewrite, parallel, coverage | 🔴 HIGH | [ ] |
| **05** | Observability & DevOps | Pulse, CI/CD, Docker | 🟢 LOW | [ ] |
| **06** | Modern Laravel Features | Attributes, jobs, broadcasts | 🟡 MEDIUM | [ ] |
| **07** | Ecosystem & Documentation | Extras, Scramble, modules | 🟢 LOW | [ ] |
| **08** | Advanced Testing | Stress, mutation, snapshot | 🟢 LOW | [ ] |
| **09** | Starter Kit Extras | 2FA, teams, web push | 🟢 LOW | [ ] |

---

## Phase 01: Foundation & Code Quality 🔴 HIGH

*Consolidate static analysis, production safeguards, and security hardening.*

- [ ] **PHPStan strict configuration**
  - Install extensions: `phpstan-deprecation-rules`
  - Set memory limit to 512M
  - Audit `phpstan/extension-installer`: remove if manually including in `phpstan.neon`
  - Evaluate need for separate `phpstan.tests.neon` config

- [ ] **ProductionSecurityChecks class**
  - Implement custom class (ref: `JustSteveKing/kit`)
  - Checks: `APP_DEBUG=false`, `APP_ENV=production`, `APP_URL` uses HTTPS, `CACHE_STORE` != array, etc.
  - Run via Artisan command or boot-time check in production

- [ ] **Security headers middleware**
  - `Strict-Transport-Security` (HSTS)
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy`
  - Evaluate `X-Frame-Options`, `Permissions-Policy`
  - CORS already strict (whitelist + credentials)

---

## Phase 02: First-Party Package Audits 🔴 HIGH

*Audit existing first-party and third-party packages against official documentation best practices.*

- [ ] **Socialite audit** (`socialite-development` skill)
  - Validate error handling, state parameter, scope configuration
  - Review `SocialCallbackAction` for race conditions and edge cases
  - Ensure testing uses `Socialite::fake()`

- [ ] **Pennant audit** (`pennant-development` skill)
  - Migrate from inline feature definition (`defineFeatures()`) to class-based features (`app/Features/`)
  - Add Pennant testing with `Feature::fake()`
  - Evaluate caching strategy and store configuration

- [ ] **Spatie Permission audit** (`laravel-permission-development` skill)
  - Evaluate: custom permission checks, events, policies
  - Review SuperAdmin `Gate::before` pattern
  - Add Policy classes for User/Role/Permission models
  - Review cache invalidation strategy

- [ ] **Email verification & password reset audit**
  - Compare with `JustSteveKing/kit` and Laravel best practices
  - Review signed URL security
  - Ensure notification queue configuration is optimal

- [ ] **Scout evaluation** (low priority)
  - Evaluate as complementary search layer for `BaseFilter`
  - Not a replacement — BaseFilter handles filtering, sorting, sparse fields

---

## Phase 03: API Hardening 🔴 HIGH

*API-level contracts, retry safety, and infrastructure observability.*

- [ ] **Rate limit response headers**
  - Add middleware to attach `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset` to all responses
  - `Retry-After` already returned by Laravel throttle on 429

- [ ] **Idempotency middleware**
  - Custom middleware (~100 lines) for `Idempotency-Key` header support
  - Scope: `POST`/`PUT`/`PATCH` endpoints (payment, order creation, etc.)
  - Cache store: Redis in production, skip atomic lock test in CI (`CACHE_STORE=array`)
  - Response headers: `Idempotency-Replayed: true/false`

- [ ] **Health check endpoint**
  - Upgrade built-in `/up` (Laravel default) to comprehensive health check
  - Check: database connection, Redis (if configured), disk writeable, queue connection
  - Standard JSON format for monitoring (Kubernetes/UptimeRobot)

---

## Phase 04: Test Modernization 🔴 HIGH

*Full test infrastructure overhaul with Pest best practices, coverage targets, and static analysis integration.*

- [ ] **Investigate PHPStan for test files** (no level downgrade)
  - Evaluate `mrpunyapal/peststan` vs manual `use function` imports
  - Determine if tests can stay at `level: max` or need separate config
  - Goal: include `modules/*/Tests/*` in PHPStan analysis

- [ ] **Parallel testing**
  - Configure in `phpunit.xml` or `composer.json` scripts
  - Resolve Spatie Permission cache race condition for parallel CI runs

- [ ] **Type coverage**
  - Enforce `pest --type-coverage` with minimum threshold
  - Add `@param`, `@return` annotations where needed

- [ ] **Code coverage**
  - Target: minimum 80% (code + branch)
  - Configure `phpunit.xml` coverage integration

- [ ] **Architecture tests**
  - Expand existing 8 arch rules (module isolation, controller/action/payload patterns)
  - Add Laravel-specific arch presets from Pest docs

- [ ] **Rewrite feature tests**
  - Use `describe()` blocks, `use function` imports
  - Integrate third-party package testing best practices:
    - Socialite: `Socialite::fake()`
    - Pennant: `Feature::fake()`
    - Sanctum: `Sanctum::actingAs()`
    - Spatie: `$this->artisan('permission:cache-reset')`

- [ ] **Rewrite unit tests**
  - 28 existing unit tests (one per Action)
  - Ensure all use Pest expectations (no PHPUnit assertions)
  - Add missing edge cases and exception paths

- [ ] **Custom expectations & helpers review**
  - Audit existing: `toBeSuccessResponse`, `toBeProblemResponse`, `toBePaginated`, `toHaveTraceId`, `toHaveSunsetHeader`
  - Add new expectations where needed

---

## Phase 05: Observability & DevOps 🟢 LOW

- [ ] **Laravel Pulse integration**
  - Configure slow query and slow route tracking
  - Set up alert thresholds

- [ ] **Laravel Telescope** (dev environment)
  - Request profiling, exception tracking, mail previews

- [ ] **CI/CD pipeline**
  - GitHub Actions with MySQL and Redis services
  - Parallel test workers with isolated databases
  - `TEST_TOKEN`-based cache/session prefixing

- [ ] **Docker setup**
  - Production-grade Dockerfile with Octane/FrankenPHP
  - `docker-compose.yml` for local development (app, MySQL, Redis, Mailpit)
  - Multi-stage build with Composer install optimization

- [ ] **Spatie Permission cache race condition**
  - Investigate and document fix for parallel CI

---

## Phase 06: Modern Laravel Features 🟡 MEDIUM

- [ ] **Migration PHP attributes**
  - Use `#[Fillable]`, `#[Hidden]`, `#[UseFactory]` (already in use for models)
  - Evaluate `#[Migration]`, `#[Table]`, `#[Column]` attributes for migration files
  - `laravel-attributes` skill available

- [ ] **Laravel 13.6 — Debounceable queued jobs**
  - Evaluate for notification/email jobs

- [ ] **Laravel 13.13 — `Bus::bulk()`**
  - Evaluate for batch dispatching many jobs

- [ ] **Sanctum private broadcast channels**
  - Evaluate `Authorization: Bearer` for WebSocket auth

- [ ] **36 new PHP 8 attributes** (Laraveldaily guide)
  - Audit which are applicable: `#[Route]`, `#[Middleware]`, `#[ScopeBindings]`, etc.

---

## Phase 07: Ecosystem & Documentation 🟢 LOW

- [ ] **Extras directory** (`modules/Extras/`)
  - Structure for non-core feature modules
  - Document module creation standards

- [ ] **Scramble API documentation improvements**
  - Uncomment `$this->configureScramble()` in `AppServiceProvider`
  - Fix missing schema detection (reflection/type investigation)
  - Implement auto-discovery helpers (transformers/extenders)

- [ ] **New modules**
  - Media storage module
  - Future modules as needed

- [ ] **Documentation strategy**
  - PRD.md vs KNOWLEDGE.md — decide approach
  - Laradocs integration for documentation directory

- [ ] **`make:module` / `module:list` improvements**
  - Use Laravel Prompts for interactive scaffolding
  - Add `module:list` command output improvements

---

## Phase 08: Advanced Testing 🟢 LOW

- [ ] **Stress testing** (Pest stress plugin) — evaluate need
- [ ] **Mutation testing** (Pest mutation plugin) — evaluate need
- [ ] **Snapshot testing** (Pest snapshot plugin) — evaluate need
- [ ] **Profanity testing** (Pest profanity plugin) — evaluate need

---

## Phase 09: Starter Kit Extras 🟢 LOW

- [ ] **Pennant feature flags** — class-based features + testing
- [ ] **Spatie teams/tenant support** — evaluate need
- [ ] **Laravel web push notifications** (`laravel-notification-channels/webpush`)
- [ ] **Two-factor authentication** (Laravel starter kit feature)
- [ ] **Authentication customization** (Laravel starter kit standard)
- [ ] **Team management** (Laravel starter kit standard)
