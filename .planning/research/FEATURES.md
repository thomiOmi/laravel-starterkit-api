# Feature Landscape

**Project:** Laravel Starterkit API
**Researched:** 2026-08-03

Feature landscape for a Laravel 13 + PHP 8.4 starterkit API with modular architecture (IAM core module). Categories below; the project's own REQUIREMENTS.md is the source of truth — this is the broader landscape and complexity signal.

## 1. Authentication & Session

| Feature | Complexity | Notes |
|---------|-----------|-------|
| Register (email/password) | S | Rate-limited; throttling on auth routes |
| Login (email/password) | S | Returns Sanctum PAT |
| Logout (revoke current token) | S | 204 response |
| Token management (list/name/revoke per device) | S | Sanctum per-device model |
| Email verification | M | Laravel built-in; API flow via signed URL |
| Password reset (forgot/reset) | M | Mailable + token; API flow |
| Change password | S | Requires current password confirmation |
| Delete account | M | Soft delete + cleanup jobs |

## 2. Social Authentication

| Feature | Complexity | Notes |
|---------|-----------|-------|
| Google OAuth (redirect/callback) | M | Socialite; guard to bind social account to user |
| GitHub OAuth (redirect/callback) | M | Same pattern; per-provider routes |
| Social account linking/unlinking | M | Decide: one social account per user per provider |

## 3. User Profile & Account

| Feature | Complexity | Notes |
|---------|-----------|-------|
| View profile | S | Identity contract resource |
| Update profile (name, avatar) | S | Avatar: uploaded file; known issue: MissingAttributeException when absent — handle gracefully |
| Change email | M | With re-verification flow |

## 4. IAM Admin (core module)

| Feature | Complexity | Notes |
|---------|-----------|-------|
| User management CRUD (admin) | M | Paginated list with Filters, view, create, update, delete |
| Role management CRUD | M | Spatie; guard-aware |
| Permission management CRUD | M | Spatie; guard-aware |
| Assign roles/permissions to user | M | Validation: role must match user's guard |
| Attach/detach permissions on role | M | `syncPermissions()` |

## 5. Feature Flags (Pennant)

| Feature | Complexity | Notes |
|---------|-----------|-------|
| List/create/update/delete flags (admin) | M | Pennant storage + API wrapper |
| Check feature state for current user | S | `Feature::for($user)->active('flag')` |

## 6. API Infrastructure

| Feature | Complexity | Notes |
|---------|-----------|-------|
| Versioned routes `/api/v1/...` | S | Done (existing convention) |
| SuccessResponse / ProblemResponse (RFC 9457) | S | Done (existing contract) |
| Rate limiting | S | Done (rate limit headers already shipped) |
| Idempotency keys for write endpoints | M | Evaluate `Idempotency-Key` header + cache with TTL; replay + `Idempotency-Replayed` header; 422 on invalid/missing key. Candidate for API hardening phase |
| API documentation (Scramble/OpenAPI) | M | Scramble preferred (reflection-based, zero noise) |
| OpenAPI contract tests | M | Validate responses match OpenAPI schema (knuckleswtf/xtest or manual) |
| Sunset middleware / API deprecation | S | Inform clients of endpoint removal (JustSteveKing/kit pattern) |
| Localization (Accept-Language) | M | `Accept-Language` header → locale; translations; respond 406 for unsupported? (kit sends 406) |
| `X-Request-Id` request correlation | S | Middleware + log context |

## 7. Observability & Operations

| Feature | Complexity | Notes |
|---------|-----------|-------|
| Health endpoint (JSON) | S | Laravel 13.6+ supports JSON health route response |
| Pulse dashboard | S | First-party monitoring; alert thresholds |
| Telescope (dev only) | S | Dev debugging, never production |
| Structured logging (context) | S | Log request ID, user ID, trace |

## 8. Quality & Delivery

| Feature | Complexity | Notes |
|---------|-----------|-------|
| CI pipeline (lint + types + tests) | M | Exists (GitHub Actions validate-skills.yml analogue; release pipeline in session history) |
| Mutation testing | M | Pest mutation plugin (P4/P8 phases) |
| Test Impact Analysis | S | `composer test:tia` exists |
| Profanity checks | S | `composer test:profanity` exists |

## 9. Deferred / v2 Candidates (not table stakes)

- Teams/organizations, Billing/subscriptions, Push notifications, Webhooks, Audit log endpoint, 2FA/TOTP (Laravel 13 ships first-party TOTP in Auth toolkit — evaluate), rate-limit bypass tiers, API keys vs PAT distinction

## Out of Scope (per project philosophy)

- JWT, UUID/integer IDs (ULID-only), IP/user-agent encryption, UserRepository, entity-level providers, HTTP DTOs (DTOs only for queues/CLI/cross-module), config files with no environment variance
