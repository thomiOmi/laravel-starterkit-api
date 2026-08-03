# Requirements: Laravel Starterkit API

**Defined:** 2026-08-03
**Core Value:** A modular, maintainable Laravel API starterkit that gives new projects a production-grade, standardized foundation without overengineering — every abstraction must earn its place.

## v1 Requirements

### Authentication

- [ ] **AUTH-01**: User can register with email and password
- [ ] **AUTH-02**: User can log in with email/password and receive a Sanctum bearer token
- [ ] **AUTH-03**: User can log out (revoke current token)
- [ ] **AUTH-04**: User can manage per-device tokens (list, name, revoke)
- [ ] **AUTH-05**: User receives verification email and can verify email address
- [ ] **AUTH-06**: User can request a password reset and reset via email link/token
- [ ] **AUTH-07**: User can change password with current password confirmation
- [ ] **AUTH-08**: User can delete their account

### Social Auth

- [ ] **SOCL-01**: User can sign up/log in with Google OAuth
- [ ] **SOCL-02**: User can sign up/log in with GitHub OAuth
- [ ] **SOCL-03**: User can link/unlink a social account to their own account

### Profile

- [ ] **PROF-01**: User can view their own profile
- [ ] **PROF-02**: User can update profile (name, avatar)
- [ ] **PROF-03**: User can change email with re-verification

### IAM Admin

- [ ] **IAM-01**: Admin can list users (filtered, paginated)
- [ ] **IAM-02**: Admin can view a user
- [ ] **IAM-03**: Admin can create a user
- [ ] **IAM-04**: Admin can update a user
- [ ] **IAM-05**: Admin can delete a user
- [ ] **IAM-06**: Admin can manage roles (list, create, update, delete)
- [ ] **IAM-07**: Admin can manage permissions (list, create, update, delete)
- [ ] **IAM-08**: Admin can assign roles/permissions to a user
- [ ] **IAM-09**: Admin can attach/detach permissions on a role

### Feature Flags (Pennant)

- [ ] **FLAG-01**: Admin can create/update/delete feature flags
- [ ] **FLAG-02**: Authenticated user can query feature flag state

### API Infrastructure

- [ ] **API-01**: All routes are versioned under `/api/v1` (already shipped)
- [ ] **API-02**: Responses use SuccessResponse/ProblemResponse RFC 9457 contract (already shipped)
- [ ] **API-03**: Auth routes enforce rate limiting with rate limit headers (already shipped)
- [ ] **API-04**: Mutating endpoints support idempotency keys (Idempotency-Key header, replay + Idempotency-Replayed)
- [ ] **API-05**: API is documented via Scramble OpenAPI

### Observability

- [ ] **OBS-01**: Health endpoint reports application health comprehensively
- [ ] **OBS-02**: Laravel Pulse monitors production app (dashboard/alert thresholds)

### Quality

- [ ] **QLTY-01**: PHPStan runs at max level on production code with zero errors
- [ ] **QLTY-02**: 100% type coverage achieved

## v2 Requirements

### Authentication

- **AUTH-09**: User can enable 2FA (TOTP) on their account

### Social Auth

- **SOCL-04**: User can sign in with additional providers (e.g., Apple, Facebook)

### Feature Flags

- **FLAG-03**: Admin can configure percentage-based gradual rollouts for flags

### API Infrastructure

- **API-07**: Sunset middleware communicates API route deprecation
- **API-08**: Accept-Language middleware supports localization/translations
- **API-09**: X-Request-Id correlation header on responses and logs

### Observability

- **OBS-03**: Structured logging with request/user context

### Quality

- **QLTY-03**: Mutation testing for test suites
- **QLTY-04**: Stress testing for critical endpoints
- **QLTY-05**: Snapshot testing
- **QLTY-06**: PHPStan for test files
- **QLTY-07**: 80% code coverage
- **QLTY-08**: Test Impact Analysis (TIA) enabled locally/CI

## Out of Scope

| Feature | Reason |
|---------|--------|
| JWT (tymon/jwt-auth) | Sanctum bearer tokens map to per-device revocation; JWT is stateless and cannot |
| UUID/integer primary keys | ULID-only, configurable ID strategy was dead code (YAGNI) |
| Encryption of IP / user-agent in personal_access_tokens | False security, breaks diagnostics; server already logs IPs |
| UserRepository / repositories | Eloquent ORM is the repository; use Model Scopes or Services |
| Entity-level providers (e.g., UserServiceProvider) | Module-level ServiceProvider only |
| DTOs/Payloads for standard HTTP flows | Only for queue jobs, CLI commands, cross-module consistency |
| OpenAPI contract tests (schema validation) | Response format is custom RFC 9457-style (SuccessResponse/ProblemResponse), not OpenAPI-shaped; Scramble docs suffice |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| AUTH-01..08 | Phase 2 | Pending |
| SOCL-01..03 | Phase 3 | Pending |
| PROF-01..03 | Phase 3 | Pending |
| IAM-01..09 | Phase 4 | Pending |
| FLAG-01..02 | Phase 5 | Pending |
| API-01..03 | Phase 1 | Complete (already shipped) |
| API-04..05 | Phase 6 | Pending |
| OBS-01..02 | Phase 7 | Pending |
| QLTY-01..02 | Phase 1 | Pending |

**Coverage:**
- v1 requirements: 34 total
- Mapped to phases: 34
- Unmapped: 0

---
*Requirements defined: 2026-08-03*
*Last updated: 2026-08-03 after roadmap creation*