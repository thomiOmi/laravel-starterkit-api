# Pitfalls & Anti-patterns

**Project:** Laravel Starterkit API
**Researched:** 2026-08-03

## Known Issues (from KNOWLEDGE.md — project-specific, verified)

1. **Module unit tests fail with `no such table: users`** — module-scoped tests run against a DB state where the main `users` table isn't migrated/available. Mitigation: ensure RefreshDatabase + migration strategy covers module tests; use the module's own factories without depending on main-user tables unless identity is truly needed (use Identity contract + factory states).

2. **`MissingAttributeException` for avatar in AuthTest** — user with no avatar throws when resource accesses `avatar`. Mitigation: `#[Missing]` attribute on optional model properties (Laravel 13) or null-safe access in Resources; never assume avatar exists.

3. **Spatie Permission cache race in parallel CI** — with `CACHE_STORE=array`, each parallel process has its own cache; permission changes in one process aren't seen by others → flaky `Forbidden` failures. Mitigation (project decision): call `forgetCachedPermissions()` in test `beforeEach` (after role seeding), pin to a consistent cache store.

## Framework pitfalls (Laravel 13)

4. **Mixing attribute styles** — using `$fillable` AND `#[Fillable]` (or `$hidden` AND `#[Hidden]`) on the same class causes inconsistent behavior. Choose attributes for new code (project convention), migrate legacy classes fully — no half-states.

5. **DebounceFor / `#[Debounce]` semantics** — debounced jobs are *dropped* when newer dispatches replace pending ones (last-dispatch-wins). Never use for critical/must-run work (emails with legal implications, charge operations). Test with `Queue::fake()` + `assertDispatchedTimes` — understand that only the final dispatch survives. Also note: debounce requires a queue worker running; in tests, fakes may bypass timing — assert dispatch, not execution timing.

6. **`Bus::bulk()` gotchas** — bulk dispatching with `maxJobs` queues in parallel; unit-of-work semantics differ from sequential dispatch. Don't wrap bulk in a transaction expecting atomicity of side effects; keep idempotent jobs.

7. **Scramble docs drift** — OpenAPI generated from code is only correct if resources/validation are explicit. Hidden rules (e.g., regex on `input()` not in Form Request) silently drift from docs. Keep all validation in Form Requests; run OpenAPI contract tests (knuckleswtf/xtest or equivalent) in CI.

8. **ULID route model binding** — default `{user}` binding works with ULID keys, but avoid implicit binding in Actions; resolve explicit ids in Controllers/Requests for testability. Use `->withTrashed()` deliberately; don't auto-trash.

9. **Rate limiting misconfiguration** — `RateLimiter::for()` scoped to wrong key (IP vs user) leaks protection; auth throttling must key on email+IP to prevent user-enumeration timing abuse. Do not double-apply throttle middleware on the same route.

10. **Sanctum token abilities vs Spatie permissions duplication** — two authorization sources drifting apart (abilities granted at token creation, permissions checked per request). Pick one source of truth per route: permissions for fine-grained, abilities only for token-scope.

11. **Socialite callback state** — must validate `state` param (CSRF) and guard against duplicate bindings (same provider+provider_id for different users) with unique constraint. Handle "email already registered" — decide: link vs reject.

## Philosophy anti-patterns (per project rules)

12. **Overengineering** — config files for values that never change between environments; services without call sites; DTOs for HTTP when Form Request + resource suffice. Rule: extract to config only when value varies per environment or 3+ call sites.

13. **Breaking ArchitectureTest.php** — architecture tests are the source of truth; if a change violates them, report to user — never auto-fix or ignore with `@phpstan-ignore`.

14. **Hardcoding module models** — `Modules\User\Models\User` referenced from other modules breaks contract isolation; use `App\Contracts\Identity`. Same for repository pattern: no UserRepository, use model scopes/Services.

## Sources

- Project KNOWLEDGE.md (Known Issues section — the top 3 are verbatim project records)
- Laravel 13 docs (attributes, DebounceFor, Bus::bulk, cache)
- laraveldaily 2026-03-18 attributes guide; square1-io/laravel-idempotency README
- JustSteveKing/kit patterns (Sunset, Accept-Language 406, contract tests)
