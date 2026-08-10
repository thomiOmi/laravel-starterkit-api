# PRD: Laravel Starterkit API

- Status: Approved
- Version: 1.0.0
- Date: 2026-08-11
- Related ADRs: see [docs/adr/README.md](../adr/README.md)

## What This Is

A production-ready Laravel 13 starterkit API that is scalable, modular, maintainable, and deliberately not overengineered. It ships a modular IAM (Identity & Access Management) core with Sanctum token auth, Spatie roles/permissions, Socialite social login, Pennant feature flags, ULID primary keys, and a standardized API contract (SuccessResponse / ProblemResponse per RFC 9457).

## Core Value

A modular, maintainable Laravel API starterkit that gives new projects a production-grade, standardized foundation without overengineering — every abstraction must earn its place.

## Personas

| Persona | Needs |
|---------|-------|
| API consumer (frontend/mobile) | Stable versioned API, predictable envelope, documented conventions, per-device auth |
| Application owner (admin) | Manage users, roles, permissions, feature flags |
| Kit adopter (developer) | Copy the kit and build a vertical product without fighting infrastructure concerns |

## In Scope (v1)

### Authentication (AUTH-01..08)

| ID | Requirement | Status |
|----|-------------|--------|
| AUTH-01 | User can register with email and password | Done (Phase 2 + idempotency, ADR-0018) |
| AUTH-02 | User can log in and receive a Sanctum bearer token | Done (Phase 2) |
| AUTH-03 | User can log out (revoke current token) | Done (Phase 2) |
| AUTH-04 | User can manage per-device tokens (list, name, revoke) | Done (Phase 2) |
| AUTH-05 | User receives verification email and can verify email address | Done (Phase 2) |
| AUTH-06 | User can request a password reset and reset via email link/token | Done (Phase 2) |
| AUTH-07 | User can change password with current password confirmation | Done (Phase 2) |
| AUTH-08 | User can delete their account | Done (Phase 2) |

### Social Auth (SOCL-01..03)

| ID | Requirement | Status |
|----|-------------|--------|
| SOCL-01 | User can sign up/log in with Google OAuth | Done (Phase 3) |
| SOCL-02 | User can sign up/log in with GitHub OAuth | Done (Phase 3) |
| SOCL-03 | User can link/unlink a social account to their own account | Done (Phase 3) |

### Profile (PROF-01..03)

| ID | Requirement | Status |
|----|-------------|--------|
| PROF-01 | User can view their own profile | Done (Phase 3) |
| PROF-02 | User can update profile (name, avatar) | Done (Phase 3 + Media, ADR-0015) |
| PROF-03 | User can change email with re-verification | Done (Phase 3) |

### IAM Admin (IAM-01..09)

| ID | Requirement | Status |
|----|-------------|--------|
| IAM-01..05 | Admin CRUD for users (list, view, create, update, delete) | Done (Phase 4) |
| IAM-06..07 | Admin manages roles and permissions (list, create, update, delete) | Done (Phase 4) |
| IAM-08..09 | Admin assigns roles/permissions to users and roles | Done (Phase 4) |

### Feature Flags (FLAG-01..02)

| ID | Requirement | Status |
|----|-------------|--------|
| FLAG-01 | Admin can create/update/delete feature flags | Done (Phase 5) |
| FLAG-02 | Authenticated user can query feature flag state | Done (Phase 5) |

### API Infrastructure (API-01..05)

| ID | Requirement | Status |
|----|-------------|--------|
| API-01 | All routes are versioned under `/api/v1` | Done (shipped) |
| API-02 | Responses use SuccessResponse/ProblemResponse RFC 9457 contract | Done (shipped) |
| API-03 | Auth routes enforce rate limiting with rate limit headers | Done (shipped) |
| API-04 | Mutating endpoints support idempotency keys | Done, opt-in per route (ADR-0018) |
| API-05 | API is documented via Scramble OpenAPI | Skipped (ADR-0019) |

### Quality (QLTY-01..02)

| ID | Requirement | Status |
|----|-------------|--------|
| QLTY-01 | PHPStan max level, zero errors | Done (shipped) |
| QLTY-02 | 100% type coverage | Done (shipped) |

## Deferred / Out of Scope (v1)

| Feature | Reason |
|---------|--------|
| JWT (tymon/jwt-auth) | Sanctum bearer tokens map to per-device revocation; JWT is stateless and cannot (ADR-0001) |
| UUID/integer primary keys | ULID-only; configurable ID strategy was dead code (ADR-0002) |
| Encryption of IP/user-agent | False security, breaks diagnostics (ADR-0003) |
| UserRepository / repositories | Eloquent ORM is the repository |
| Entity-level providers | Module-level ServiceProvider only |
| DTOs/Payloads for standard HTTP flows | Only for queue jobs, CLI, cross-module consistency |
| Health endpoint (OBS-01) | Deferred — see `TASKS.md` Phase 7 (plan recorded, not executed) |
| Laravel Pulse (OBS-02) | Skipped — web dashboard not relevant for API-only kit |
| Accounting module | Domain-specific, cannot be generalized (ADR-0016) |
| Image processing | Deferred — 10MB free-typed limit; intervention/image recipe documented (ADR-0015) |

## Architecture Overview

- **Modules**: `Modules/IAM` (core) + `Modules/Media` (first feature module). Modules communicate through contracts (`App\Contracts\Identity`), never direct imports (ADR-0004, ADR-0005).
- **Routing**: single discovery via `RouteServiceProvider`; names `v1.{module}.{name}` (ADR-0008).
- **Controllers**: plain `abstract readonly Controller` base; invokable `final readonly` controllers (ADR-0009).
- **Responses**: `SuccessResponse` / `ProblemResponse` (RFC 9457), no `success` boolean.
- **Testing**: Pest 5, `describe()`/`it()`, typed helpers in `tests/Helpers.php`, module groups `module:{name}` — see [docs/testing.md](../testing.md).
- **Quality gates**: `composer lint` → `types:check` → `test:quality` (100% coverage) → `ci:check` before push.

## Constraints

- **Tech stack**: Laravel 13 + PHP 8.4 + MySQL (no Redis requirement; cache/queue default to database)
- **Auth**: Laravel Sanctum bearer tokens (per-device session management) — settled decision (ADR-0001)
- **IDs**: ULID-only primary keys — settled decision (ADR-0002)
- **Language**: All datetime fields in API responses MUST use `Y-m-d H:i:s`
- **Testing**: Pest feature tests with RefreshDatabase; every change requires a test
- **No overengineering**: no config files, middleware, or services for scenarios that do not exist yet

## Metrics / Success Criteria

- Full test suite green serial + parallel, 100% code and type coverage, `composer ci:check` pass
- Architecture test suite (46/46) is the single source of truth for conventions; changes require human approval
- New project can scaffold a module (`make:module`) and extend IAM patterns without fighting infrastructure

## Document Relations

- **Decisions**: [docs/adr/](../adr/README.md) — why each convention exists
- **Execution status**: [TASKS.md](../../TASKS.md) — phase-by-phase tracking
- **Technical details**: [docs/](../README.md) — api-standard, architecture, auth, testing, rbac, etc.
- **Historical planning**: GSD artifacts (`.planning/`, removed 2026-08-11) — preserved in git history only; superseded by `docs/` and `.ai/rules/`

## Revision History

| Version | Date | Change |
|---------|------|--------|
| 1.0.0 | 2026-08-11 | Migrated from `.planning/PROJECT.md` + `REQUIREMENTS.md` into canonical `docs/`; corrected stale stack facts (no Redis); requirement statuses updated to actual shipped state |
