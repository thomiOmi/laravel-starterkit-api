# Laravel Starterkit API

## What This Is

A production-ready Laravel 13 starterkit API that is scalable, modular, maintainable, and deliberately not overengineered. It ships a modular IAM (Identity & Access Management) core with Sanctum token auth, Spatie roles/permissions, Socialite social login, Pennant feature flags, ULID primary keys, and a standardized API contract (SuccessResponse / ProblemResponse per RFC 9457). The roadmap drives the kit from a solid foundation toward observability, modern Laravel features, and advanced testing so any new project can start from an industrial baseline.

## Core Value

A modular, maintainable Laravel API starterkit that gives new projects a production-grade, standardized foundation without overengineering — every abstraction must earn its place.

## Requirements

### Validated

(None yet — ship to validate)

### Active

- [ ] Foundation & code quality: PHPStan strict config, production security checks, security headers middleware
- [ ] First-party package audits: Socialite, Pennant, Spatie Permission, email verification & password reset, Scout evaluation
- [ ] API hardening: idempotency middleware, comprehensive health check endpoint
- [ ] Test modernization: PHPStan for tests, parallel testing, type coverage, 80% code coverage, feature/unit test rewrites
- [ ] Observability & DevOps: Laravel Pulse, Telescope (dev), Docker setup, Spatie permission cache race fix
- [ ] Modern Laravel features: migration PHP attributes, debounceable jobs, Bus::bulk(), Sanctum private broadcast channels
- [ ] Ecosystem & documentation: Extras directory, Scramble API docs, new modules (media storage), docs strategy, make:module improvements
- [ ] Advanced testing: stress, mutation, snapshot, profanity plugins
- [ ] Starter kit extras: class-based Pennant features, 2FA, team management, web push notifications

### Out of Scope

- JWT (tymon/jwt-auth) — Sanctum bearer tokens map to per-device revocation; JWT is stateless and cannot
- UUID/integer primary keys — ULID-only, configurable ID strategy was dead code (YAGNI)
- Encryption of IP address / user-agent in `personal_access_tokens` — false security, breaks diagnostics; server already logs IPs
- UserRepository / repositories — Eloquent ORM is the repository; use Model Scopes or Services instead
- Entity-level providers (e.g., `UserServiceProvider`) — module-level `ServiceProvider` only
- DTOs/Payloads for standard HTTP flows — only for queue jobs, CLI commands, cross-module consistency

## Context

**Architecture rules (from KNOWLEDGE.md):**
- Identity contract: `App\Contracts\Identity` for user type-hinting in `app/` layer — never import `Modules\User\Models\User` directly
- Flat structure: shallow controller hierarchies, no nested sub-folders unless file count > 20
- FormRequests for HTTP validation; JsonResource for API responses
- Delegated routing: each module registers routes in its own ServiceProvider
- Thin controllers: logic goes to Actions (orchestration) or Services (domain logic)
- Roles eager-loading narrowed to `roles:id,name,guard_name` (Spatie guard_name requirements)
- ULID primary keys everywhere; routes use `whereUlid`

**Established stack:** Laravel 13 · PHP 8.4 · MySQL · Sanctum · Spatie Permission · Socialite · Pennant · Redis · Pint · Pest · PHPStan · Tinker · Pail. Release pipeline: `release.yml` quality gate (Pint → PHPStan → Pest), shipmark versioning, Jules-only agent workflows.

**Known issues to address:**
- Module unit tests fail with `no such table: users` (migration ordering — module tests run before global migrations)
- `AuthTest` avatar `MissingAttributeException` (User model `$appends`/`$with` config)
- Spatie Permission cache race condition in parallel CI (`CACHE_STORE=array` + `forgetCachedPermissions()` mitigation; long-term: TEST_TOKEN cache prefix)

**Milestone context:** ROADMAP.md migrated from existing project roadmap; phases 01, 03 (rate limit headers), 04 (arch tests, expectations review), 05 (CI/CD) partially complete. Remaining scope is the Active list above.

## Constraints

- **Tech stack**: Laravel 13 + PHP 8.4 + MySQL — mandated by project identity
- **Auth**: Laravel Sanctum bearer tokens (per-device session management) — settled decision
- **IDs**: ULID-only primary keys — settled decision
- **Language**: All datetime fields in API responses MUST use `Y-m-d H:i:s` format
- **Testing**: Pest feature tests with RefreshDatabase; every change requires a test
- **No overengineering**: no config files, middleware, or services for scenarios that do not exist yet; inline code with defaults over configurable flags

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Sanctum bearer tokens over JWT | Per-device token revocation (list/revoke/name) maps to device-per-token model | ✓ Good |
| ULID-only IDs | Removes dead code paths, reduces cognitive load | ✓ Good |
| No IP/user-agent encryption | False security, breaks diagnostics; server logs IPs anyway | ✓ Good |
| Roles eager-load `roles:id,name,guard_name` | Prevents lazy-loading guard_name, satisfies Spatie permission checks | ✓ Good |
| Module ServiceProvider routing | Modules communicate through contracts, explicit route registration | — Pending |
| `Identity` contract for user type-hinting | Module isolation, no hard imports of user model across modules | — Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd-transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-08-03 after initialization*
