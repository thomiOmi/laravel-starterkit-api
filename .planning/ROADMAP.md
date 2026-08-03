# Roadmap: Laravel Starterkit API

## Overview

A production-ready Laravel 13 API starterkit. The journey consolidates the partially-complete foundation (PHPStan, arch tests, rate-limit headers, CI/CD already shipped) into a vertically-delivered, end-to-end capability surface: a hardened code-quality baseline first, then full account lifecycle, social authentication, IAM administration, feature flags, API hardening and documentation, observability, and finally modern Laravel features plus advanced testing. Every phase ships a usable, verifiable slice of the starterkit rather than an abstract layer.

## Phases

**Phase Numbering:**

- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

- [ ] **Phase 1: Quality Foundation** - Enforce PHPStan max + 100% type coverage and validate the shipped API contract and rate limiting
- [ ] **Phase 2: Authentication** - Complete account lifecycle (register, login, tokens, verify, reset, password, delete)
- [ ] **Phase 3: Social Auth & Profile** - Google/GitHub OAuth sign-in/link with profile management
- [ ] **Phase 4: IAM Admin** - Admin CRUD for users, roles, permissions with assignment management
- [ ] **Phase 5: Feature Flags** - Pennant flag management and per-user availability
- [ ] **Phase 6: API Hardening & Documentation** - Idempotency keys and Scramble OpenAPI docs
- [ ] **Phase 7: Observability** - Comprehensive health endpoint and Laravel Pulse production monitoring
- [ ] **Phase 8: Modern Features & Advanced Testing** - Modern queue/attributes features plus mutation/stress/snapshot testing

## Phase Details

### Phase 1: Quality Foundation

**Goal**: A failing-fast quality baseline: PHPStan at max level with zero errors, 100% type coverage, and the shipped API contract (SuccessResponse/ProblemResponse, rate-limit headers) verified end-to-end.
**Mode**: mvp
**Depends on**: Nothing (first phase)
**Requirements**: QLTY-01, QLTY-02, API-01, API-02, API-03
**Success Criteria** (what must be TRUE):

  1. `composer types:check` exits zero at PHPStan level max with no errors
  2. `composer test:quality` reports 100% type coverage
  3. Existing contract tests confirm SuccessResponse/ProblemResponse shape and rate-limit headers on auth routes

**Plans**: 2 plans

Plans:
**Wave 1**

- [x] 01-01: Rate-limit contract verification (TDD) - AuthRateLimitTest for all 4 throttle:auth routes, 429 header fix in bootstrap/app.php

**Wave 2** *(blocked on Wave 1 completion)*

- [ ] 01-02: PHPStan module-test scope + quality gates - phpstan.neon exclude removal, types:check/test:quality/ci:check green

### Phase 2: Authentication

**Goal**: Users can manage the complete account lifecycle: register, login, per-device tokens, email verification, password reset, change password, and account deletion.
**Mode**: mvp
**Depends on**: Phase 1
**Requirements**: AUTH-01, AUTH-02, AUTH-03, AUTH-04, AUTH-05, AUTH-06, AUTH-07, AUTH-08
**Success Criteria** (what must be TRUE):

  1. User can register with email/password and immediately log in with the issued token
  2. User can list, name, and revoke personal access tokens per device
  3. User can verify their email via a signed link and reset a forgotten password via email flow
  4. User can change password (current password required) and delete their account
  5. All auth endpoints rate-limited with throttle headers

**Plans**: TBD

Plans:

- [ ] 02-01: <TBD during planning>
- [ ] 02-02: <TBD during planning>

### Phase 3: Social Auth & Profile

**Goal**: Users sign in with Google or GitHub through Socialite, link/unlink social accounts to their local account, and manage their profile (name, avatar, email re-verification).
**Mode**: mvp
**Depends on**: Phase 2
**Requirements**: SOCL-01, SOCL-02, SOCL-03, PROF-01, PROF-02, PROF-03
**Success Criteria** (what must be TRUE):

  1. User can sign up/log in with Google OAuth and with GitHub OAuth (state validated, unique bindings enforced)
  2. User can link an additional social account and unlink it
  3. User can view and update their profile (name, avatar) without a MissingAttributeException when avatar is absent
  4. User can change email and must re-verify the new address

**Plans**: TBD

Plans:

- [ ] 03-01: <TBD during planning>
- [ ] 03-02: <TBD during planning>

### Phase 4: IAM Admin

**Goal**: Admins administrate identity end-to-end: users, roles, and permissions CRUD plus role/permission assignment, all via the Identity contract without hardcoded module imports.
**Mode**: mvp
**Depends on**: Phase 3
**Requirements**: IAM-01, IAM-02, IAM-03, IAM-04, IAM-05, IAM-06, IAM-07, IAM-08, IAM-09
**Success Criteria** (what must be TRUE):

  1. Admin can list (filtered, paginated), view, create, update, and delete users
  2. Admin can create, update, and delete roles and permissions
  3. Admin can assign roles/permissions to a user and attach/detach permissions on a role
  4. Permission checks on module routes work through the Identity contract and guard mismatch returns 403
  5. Spatie permission cache race avoided in parallel test runs

**Plans**: TBD

Plans:

- [ ] 04-01: <TBD during planning>
- [ ] 04-02: <TBD during planning>

### Phase 5: Feature Flags

**Goal**: Admins manage Pennant feature flags through the API and users see their flag state.
**Mode**: mvp
**Depends on**: Phase 4
**Requirements**: FLAG-01, FLAG-02
**Success Criteria** (what must be TRUE):

  1. Admin can create, update, and delete feature flags via the API
  2. Authenticated user can query whether a flag is active for them
  3. Flags are covered by `Feature::fake()` tests

**Plans**: 1 plan

Plans:

- [ ] 05-01: Admin Pennant flag management and user-facing state

### Phase 6: API Hardening & Documentation

**Goal**: Mutating endpoints are protected against duplicate submissions with idempotency keys, and the whole API is documented via Scramble OpenAPI.
**Mode**: mvp
**Depends on**: Phase 5
**Requirements**: API-04, API-05
**Success Criteria** (what must be TRUE):

  1. POST/PUT/PATCH endpoints reject invalid `Idempotency-Key` with 422 and accept a valid V4 UUID
  2. Replayed requests return the stored response with `Idempotency-Replayed: true`
  3. Scramble serves OpenAPI docs for all v1 routes with correct schemas

**Plans**: TBD

Plans:

- [ ] 06-01: <TBD during planning>
- [ ] 06-02: <TBD during planning>

### Phase 7: Observability

**Goal**: The API reports its health comprehensively and Laravel Pulse monitors production performance (slow queries, slow routes, alert thresholds).
**Mode**: mvp
**Depends on**: Phase 6
**Requirements**: OBS-01, OBS-02
**Success Criteria** (what must be TRUE):

  1. Health endpoint reports app/dependency status (database, Redis, disk, queues) in standard JSON
  2. Laravel Pulse dashboard exposed and slow query/route tracking configured with alert thresholds
  3. Health and Pulse are verified with tests where applicable

**Plans**: TBD

Plans:

- [ ] 07-01: <TBD during planning>

### Phase 8: Modern Features & Advanced Testing

**Goal**: Adopt modern Laravel 13 capabilities (migration/model attributes, debounceable jobs, Bus::bulk()) and round out the test arsenal (stress, mutation, snapshot, profanity) for kit release readiness.
**Mode**: mvp
**Depends on**: Phase 7
**Requirements**: (none of the v1 tracked requirements; delivers v2 candidates and kit readiness)
**Success Criteria** (what must be TRUE):

  1. Modern PHP attributes (migration/table/column and related) adopted consistently with no mixed legacy properties
  2. Debounceable jobs and Bus::bulk evaluated and used where they genuinely apply (no forced adoption)
  3. Mutation and stress testing configured; mutation score threshold enforced
  4. Starter kit extras evaluated (2FA, teams, web push) with accept/reject recorded

**Plans**: TBD

Plans:

- [ ] 08-01: <TBD during planning>
- [ ] 08-02: <TBD during planning>

## Progress

**Execution Order:**
Phases execute in numeric order: 1 to 8.

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Quality Foundation | 0/1 | Not started | - |
| 2. Authentication | 0/2 | Not started | - |
| 3. Social Auth & Profile | 0/2 | Not started | - |
| 4. IAM Admin | 0/2 | Not started | - |
| 5. Feature Flags | 0/1 | Not started | - |
| 6. API Hardening & Documentation | 0/2 | Not started | - |
| 7. Observability | 0/1 | Not started | - |
| 8. Modern Features & Advanced Testing | 0/2 | Not started | - |
