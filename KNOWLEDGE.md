# KNOWLEDGE.md

**Project:** Laravel Starterkit API
**Stack:** Laravel 13 · PHP 8.4 · MySQL · Laravel Sanctum · Spatie Permission · Laravel Socialite · Laravel Passport · Laravel Pint · Pest · PHPStan · Laravel Pennant · Redis · Laravel Tinker
**Created:** 2026-06-28

---

## Preferences

<!-- Personal working preferences applied across all projects.
     Things you want the agent to do without being asked every time.

     Format:
     ### [Preference name] — [YYYY-MM-DD]
     **Rule:** [What to always do or never do]
     **Context:** [When this applies — all projects, or specific conditions]
-->

---

## Decisions

### Sanctum Bearer Token over JWT — 2026-06-29
**Decision:** Use Laravel Sanctum Bearer tokens instead of JWT (e.g., tymon/jwt-auth).
**Reason:** The project has per-device session management built in, allowing users to manage individual device tokens (revoke, list, name). JWT is stateless and does not support per-device revocation; Sanctum's token-based approach maps naturally to a device-per-token model.
**Applies to:** This project only.

---

## Conventions

<!-- Cross-project conventions you want applied consistently.

     Format:
     ### [Convention name]
     **Rule:** [What to do]
     **Exception:** [Any exceptions, or "none"]
-->

---

## Known Issues

<!-- Cross-project pitfalls or recurring problems to watch out for. -->

| Issue | Context | Added |
|-------|---------|-------|
|  |  |  |

---

## Session History

### Sanctum Bearer Token over JWT — 2026-06-29
**Decision:** Use Laravel Sanctum Bearer tokens instead of JWT (e.g., tymon/jwt-auth).
**Reason:** The project has per-device session management built in, allowing users to manage individual device tokens (revoke, list, name). JWT is stateless and does not support per-device revocation; Sanctum's token-based approach maps naturally to a device-per-token model.
**Applies to:** This project only.

---

## Update Log

<!-- Track when significant entries were added or changed.
     Format: - [YYYY-MM-DD]: [What changed]
-->
