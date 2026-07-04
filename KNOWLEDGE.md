# Project Knowledge & Decision Log

**Project:** Laravel Starterkit API
**Stack:** Laravel 13 · PHP 8.4 · MySQL · Laravel Sanctum · Spatie Permission · Laravel Socialite · Laravel Pint · Pest · PHPStan · Laravel Pennant · Redis · Laravel Tinker · Laravel Pao · Laravel Pail
**Created:** 2026-06-28
**Last Updated:** 2026-06-29

---

## Session History

### 1. Refactor Architecture Manifesto (2026-06-29)
**Decision:** Move architectural "Laws" from KNOWLEDGE.md to `.ai/guidelines/general.md`.
**Reason:** AI Agents (Cursor, Claude Code) load guidelines upfront. Moving laws to guidelines ensures they are always in context during the coding process.

### 2. Sanctum Bearer Token over JWT (2026-06-29)
**Decision:** Use Laravel Sanctum Bearer tokens instead of JWT.
**Reason:** Supports per-device session management (revoke, list, name). JWT is stateless and harder to revoke individually without complex blacklisting.

---

## Decisions

<!-- Significant design choices and why they were made.
     Prevents relitigating settled decisions in future sessions.

     Format:
     ### [Title] — [YYYY-MM-DD]
     **Decision:** [What was decided — one sentence]
     **Reason:** [Why this choice]
     **Alternatives rejected:** [What was not chosen and why]
     **Impact:** [What this affects going forward]
-->

---

## Conventions

<!-- Cross-project conventions you want applied consistently.
     (Note: Core coding standards are in docs/coding-standards.md and .ai/guidelines/general.md)
-->

---

## Known Issues

| Issue | Context | Added |
|-------|---------|-------|
| Module Unit tests (Auth\Unit) fail with `no such table: users` | Migration ordering — module tests may run before global migrations. | 2026-06-29 |
| AuthTest `avatar` MissingAttributeException | Issue with the User model `$appends` config in test environments. | 2026-06-29 |

---

## Update Log
- 2026-06-29: Massive cleanup. Transferred core laws to AI Guidelines. Rewrote project documentation in English.
