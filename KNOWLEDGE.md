# KNOWLEDGE.md

**Project:** Laravel Starterkit API
**Stack:** Laravel 13 · PHP 8.4 · MySQL · Laravel Sanctum · Spatie Permission · Laravel Socialite · Laravel Pint · Pest · PHPStan · Laravel Pennant · Redis · Laravel Tinker · Laravel Pao · Laravel Pail
**Created:** 2026-06-28

---

# Project Architecture Rules

## Must Do
- **Identity Contract:** Always use `App\Contracts\Identity` for user-related type-hinting in the `app/` layer.
- **Flat Structure:** Keep controller hierarchies shallow. No nested sub-folders (e.g., `IAM/Controllers/V1/`) unless file count > 20.
- **FormRequests:** Always use `FormRequest` for HTTP validation.
- **API Resources:** Always use `JsonResource` for API responses to ensure contract stability.
- **Async DTOs:** Use DTOs/Payloads ONLY for Queue Jobs, CLI commands, or cross-module data consistency.
- **Delegated Routing:** Routes must be registered in the module's own `ServiceProvider`.

## Must Not Do
- **No UserRepository:** Do NOT create repositories. Eloquent ORM is the repository. Use Model Scopes or Services instead.
- **No Hardcoding:** Never import `Modules\User\Models\User` in the `app/` directory or other modules. Use the `Identity` contract.
- **No Redundant DTOs:** Do NOT create DTOs for standard HTTP flows if a `FormRequest` is sufficient.
- **No Fat Logic:** Controllers should be thin. Move logic to `Actions` (orchestration) or `Services` (domain logic).
- **No Provider Bloat:** Do NOT create `UserServiceProvider` or similar entity-level providers. Use module-level `ServiceProvider` (e.g., `IAMServiceProvider`).

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
| Spatie Permission cache race condition in parallel CI | When CI uses shared Redis, parallel workers share `spatie.permission.cache` key. Worker A creates permission → Worker B's stale cache says it doesn't exist ⇒ `PermissionDoesNotExist`. *Mitigation:* `phpunit.xml` sets `CACHE_STORE=array` + `tests/Pest.php` calls `forgetCachedPermissions()` in `beforeEach`. *Long-term:* Investigate `TEST_TOKEN`-based cache prefix for shared-service CI. | 2026-07-06 |

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
