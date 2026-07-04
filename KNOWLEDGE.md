# Project Knowledge & Decision Log

**Project:** Laravel Starterkit API
**Stack:** Laravel 13 · PHP 8.4 · MySQL · Laravel Sanctum · Spatie Permission · Laravel Socialite · Laravel Pint · Pest · PHPStan · Laravel Pennant · Redis · Laravel Tinker · Laravel Pao · Laravel Pail
**Created:** 2026-06-28
**Last Updated:** 2026-06-29

---

## Session History

### 1. Refactor Architecture Manifesto (2026-06-29)
**Decision:** Move core architectural "Laws" from KNOWLEDGE.md to `.ai/guidelines/general.md`.
**Reason:** Guidelines are loaded upfront by AI agents, ensuring the laws are always in the context window during development.

### 2. Standardization of Action-Payload (2026-06-29)
**Decision:** All business logic must use the Action-Payload pattern.
**Reason:** Ensures consistency between HTTP and asynchronous (Queue/CLI) contexts.

---

## Decision Log

### Sanctum Bearer Token over JWT — 2026-06-29
**Decision:** Use Laravel Sanctum Bearer tokens instead of JWT.
**Reason:** Supports per-device session management (revoke, list, name). JWT is stateless and harder to revoke individually.

---

## Known Issues

| Issue | Context | Added |
|-------|---------|-------|
| Module Unit tests (Auth\Unit) fail with `no such table: users` | Migration ordering — module tests may run before global migrations. | 2026-06-29 |
| AuthTest `avatar` MissingAttributeException | Issue with the User model `$appends` config in test environments. | 2026-06-29 |

---

## Update Log
- 2026-06-29: Massive refactor. Transferred core laws to AI Guidelines. Rewrote documentation in English using a Storybook approach.
