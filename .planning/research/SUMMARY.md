# Research Summary

**Project:** Laravel Starterkit API
**Researched:** 2026-08-03

## Project Summary

Laravel Starterkit API is a production-ready, modular Laravel 13 + PHP 8.4 API starterkit. Core: IAM module (users, roles, permissions) with Sanctum PAT auth, Socialite social login, Pennant feature flags. Architecture: module-DDD with Actions/Controllers/Filters/Payloads/Resources, thin controllers, Identity contract (`App\Contracts\Identity`), ULID-only keys, `SuccessResponse`/`ProblemResponse` (RFC 9457) contract, `Y-m-d H:i:s` datetimes. Testing: Pest 5 with custom expectations, TIA, parallel CI, architecture tests as source of truth. Philosophy: production-ready, not overengineered; Laravel-native first; explicit over magic.

## Stack

| Layer | Choice | Notes |
|-------|--------|-------|
| Framework | Laravel 13 + PHP 8.4 | Native PHP attributes (36+), DebounceFor jobs, Bus::bulk |
| Database | MySQL | ULID primary keys |
| Auth | Sanctum 4 (PAT, per-device) | Settled over JWT |
| Permissions | Spatie laravel-permission 6 | Guard-aware; sparse eager-load `roles:id,name,guard_name` |
| Social | Socialite 5 | Google/GitHub |
| Flags | Pennant 1 | Class-based features |
| API docs | Scramble (OpenAPI) | Reflection-based; contract tests in CI |
| Quality | Pint, PHPStan max, Pest 5 (+ mutation/stress plugins), Rector 2 | Full gates in composer scripts |
| Ops | Pulse (prod), Telescope (dev), health route JSON, X-Request-Id | |
| Hardening | Idempotency keys, Sunset middleware, Accept-Language, rate limits | Evaluate in API hardening phase |

## Table Stakes (must ship)

1. Auth: register, login, logout, token management, email verification, password reset, change password, delete account
2. Social login: Google + GitHub (redirect/callback/link)
3. Profile: view/update (name, avatar, email w/ re-verification)
4. IAM admin CRUD: users, roles, permissions, role/permission assignment
5. Pennant flags: admin CRUD + per-user state check
6. API infra: versioned routes, response contract, rate limiting (shipped), pagination meta
7. Quality: feature tests per CRUD op, unit tests per Action, arch tests, CI gates

## Watch Out For

1. Module tests `no such table: users` — fix migration/test setup, don't couple to main-user tables
2. `MissingAttributeException` avatar — use `#[Missing]` / null-safe resource access
3. Spatie cache race in parallel CI — `forgetCachedPermissions()` in beforeEach, consistent store
4. Attribute consistency — never mix `$fillable` with `#[Fillable]`
5. Debounce semantics — last-dispatch-wins; never for critical work
6. Authorization drift — abilities vs permissions: one source of truth
7. Never auto-fix ArchitectureTest.php violations; report to user
8. Socialite: validate state, unique provider binding, email-conflict policy

## Research Gaps / Decisions Needed

- Idempotency keys: in scope for P3 hardening? (recommend yes, MITM-style implementation)
- Scramble vs Scribe: STACK recommends Scramble (roadmap mentions; verify P7)
- 2FA/TOTP: Laravel 13 first-party — v2 candidate only
- Avatar storage driver: local vs S3 — defer until real deployment
- Password rules: project standard (min 8, letters/numbers) — confirm in requirements

## Sources

- Laravel 13 changelog/docs; laraveldaily attributes guide (2026-03-18); Laravel News 13.6.0 (2026-04-22), 13.13.0 (2026-06-03)
- JustSteveKing/kit (2026-02-23); square1-io/laravel-idempotency
- Project: KNOWLEDGE.md, ROADMAP.md, AGENTS.md (authoritative for conventions)
