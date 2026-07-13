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

### Release Pipeline & Agent Workflow Standardisation — 2026-07-13
**Decision:** Create `release.yml` with quality gate (Pint→PHPStan→Pest), `shipmark` for versioning, and `gh release create --generate-notes`. Strip all agent workflow YMLs (`agent-*.yml`, `bug-fixer.yml`, `ci-failure-fix.yml`) to Jules-only — no checkout/setup/install/test on GitHub runner. All `inputs` removed; verification loops delegated to Jules' own prompt.
**Reason:** Shipmark handles version bump + changelog idempotently without creating a redundant GitHub Release. Agent workflows were bloated with CI steps that belong in `ci.yml` — Jules runs in a remote container, not on the repo's runner. Reduces GitHub Actions minutes and avoids PR-branch-sync friction.
**Alternatives rejected:** `semantic-release` (too JS-heavy for a Laravel project), single monolithic agent YML (loss of per-workflow granularity).
**Impact:** `composer release:dry` available locally. `release.yml` will fail fast if pipeline steps fail. Future agent workflows follow the same Jules-only pattern.

### ULID Standardisation — 2026-07-13
**Decision:** Remove primary key type config (`architecture.model.default_id`) and all UUID/integer conditionals. All IDs are ULID-only. Macro `whereId` deleted from `AppServiceProvider`; routes use `whereUlid`. Controllers/Resources have match guards simplified to direct `(string)` or `->id` access. `HasDefaultBehavior::initializeHasDefaultBehavior()` removed. `MakeModule::getMigrationIdColumn()` hardcoded to ulid.
**Reason:** Reducing surface area for bugs and cognitive load. The project only uses ULID; supporting UUID/integer selection is dead code.
**Alternatives rejected:** Keeping the config switch "for future flexibility" (YAGNI — adds complexity with no benefit).
**Impact:** All future modules will use ULID primary keys automatically.

### No Encryption for IP Address & User-Agent — 2026-07-13
**Decision:** `ip_address` and `user_agent` in `personal_access_tokens` are not encrypted. Column stays VARCHAR(45) / TEXT. Cast `'ip_address' => 'encrypted'` removed from `PersonalAccessToken` model.
**Reason:** IP addresses are already logged by the server (Nginx, load balancer) — encrypting them in the database provides a false sense of security with no real benefit, while removing utility (diagnostics, rate-limiting by IP, geolocation). User-agent is not sensitive data. Encryption also forces column resizing (VARCHAR(45) → TEXT) because base64 output is ~3× the input size.
**Alternatives rejected:** Encrypt via Laravel casts (adds storage overhead, removes search/filter capability, causes `Data too long for column` errors).
**Impact:** AI agents and developers should not recommend encryption for these fields. The starterkit stays simple.

### Postman Collection Setup — 2026-07-13
**Decision:** Generate Postman Collection v2.1.0 JSON from 28 YAML request files in `postman/` directory. Uploaded to workspace "My Workspace" along with an environment (`base_url`, `auth_token`, ULID variable placeholders). Bearer auth at collection level; Login request has auto-token script on test event.
**Reason:** Team needs a shareable API collection for development and testing. Postman workspace integration via MCP.
**Applies to:** This project only.

---

## Update Log

<!-- Track when significant entries were added or changed.
     Format: - [YYYY-MM-DD]: [What changed]
-->
- 2026-06-29: Added testing improvements — 9-item plan covering Sanctum::actingAs, Notification::assertSentTo, AssertableJson, Event::fake, travelTo. Removed DeviceManagementTest Sanctum::actingAs (reverted to original Bearer header approach due to token count issues).
- 2026-06-29: Fixed `MissingAttributeException` for `avatar`/`deleted_at` — added all nullable columns (`provider`, `provider_id`, `avatar`, `deleted_at`) to UserFactory default definition. Fixed PermissionCRUDTest: `assertSoftDeleted` for soft-delete model, 403 expectation for non-existent permission delete (controller returns 403 when handle returns false).
- 2026-07-13: Added release pipeline (`release.yml`, `.shipmarkrc.yml`), Jules-only agent workflows (5 files), `composer release:dry` script.
- 2026-07-13: Fixed 3 Jules PR audit issues — `whereId` RouteRegistrar macro, `findOrFail` in all Actions, `guard_name` hidden from API responses.
- 2026-07-13: ULID standardisation — removed configurable primary key strategy, deleted `whereId` macro, simplified Controllers/Resources match guards, removed redundant `initializeHasDefaultBehavior()`.
- 2026-07-13: Generated and uploaded Postman Collection (28 requests, 4 folders) + environment to "My Workspace".
- 2026-07-13: Removed `ip_address' => 'encrypted'` cast from PersonalAccessToken — IP/user-agent does not need encryption in database.
- 2026-07-14: Executed full ORM/Code audit fixes — H1 (10 controllers param name mismatch), H2 (auth on UserList/UserAssignRoles/Bulk controllers), H3 (N+1 DeleteUserAction), H4 (dead param LogoutOtherDevicesAction), M1 (redundant auth Role/Permission controllers), M3 (whereUlid devices route), M5 (cache-after-DB BulkDeleteRoles), M7-M8 (migration column type + wrong down()), M9 (HasDefaultBehavior on PersonalAccessToken).
