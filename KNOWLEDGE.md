# KNOWLEDGE.md

**Project:** Laravel Starterkit API
**Stack:** Laravel 13 · PHP 8.4 · MySQL · Laravel Sanctum · Spatie Permission · Laravel Socialite · Laravel Passport · Laravel Pint · Pest · PHPStan · Laravel Pennant · Redis · Laravel Tinker
**Created:** 2026-06-28

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
| Module Unit tests (Auth\Unit) fail with `no such table: users` | Migration ordering — module tests may run before global migrations. Pre-existing, outside current session scope. | 2026-06-29 |
| AuthTest `avatar` MissingAttributeException | Pre-existing issue with the User model `$appends` / `$with` config. | 2026-06-29 |

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
- 2026-06-29: Added testing improvements — 9-item plan covering Sanctum::actingAs, Notification::assertSentTo, AssertableJson, Event::fake, travelTo. Removed DeviceManagementTest Sanctum::actingAs (reverted to original Bearer header approach due to token count issues).
- 2026-06-29: Fixed `MissingAttributeException` for `avatar`/`deleted_at` — added all nullable columns (`provider`, `provider_id`, `avatar`, `deleted_at`) to UserFactory default definition. Fixed PermissionCRUDTest: `assertSoftDeleted` for soft-delete model, 403 expectation for non-existent permission delete (controller returns 403 when handle returns false).
