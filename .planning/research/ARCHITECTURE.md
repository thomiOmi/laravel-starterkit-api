# Architecture Patterns

**Project:** Laravel Starterkit API
**Researched:** 2026-08-03

## Module Structure (project convention)

```
Modules/{Feature}/
├── Actions/          # final readonly invokable/class actions (business logic)
├── Controllers/      # thin controllers, delegated routing via module ServiceProvider
├── Filters/          # query filters for list endpoints
├── Payloads/         # data transfer (only queues/CLI/cross-module boundaries)
├── Requests/         # Form Requests with validation
├── Resources/        # JsonResource response shapes
├── Services/         # cross-module services
└── Database/         # migrations, factories, seeders
```

Core (IAM) stays minimal; feature modules communicate via contracts (`App\Contracts\Identity`), never direct imports of `Modules\User\Models\User`.

## Identity Contract

- All modules depend on `App\Contracts\Identity` (auth user abstraction), not the concrete User model.
- Guards permission checks with Spatie `HasRoles` on the identity.

## Model Conventions (Laravel 13 PHP Attributes)

- Use attributes over properties: `#[Fillable]`, `#[Hidden]`, `#[UseFactory]`, `#[UseResource]`, `#[UseResourceCollection]`, `#[Table]`, `#[Migration]`, `#[Column]`, `#[Cache]` (cached model), `#[WithoutRelations]` (precision loading), `#[Searchable]` (Scout).
- Consistency rule: do not mix `$fillable` with `#[Fillable]` — pick one per class.
- Cast enum columns: `'status' => StatusEnum::class`. ULID primary keys standard.

## Controllers & Validation

- Thin controllers: parse input → delegate to Action/Service → return `JsonResource` or `SuccessResponse`.
- All validation visible in Form Requests: `#[ErrorBag]`, `#[RedirectToRoute]`, `#[StopOnFirstFailure]` attributes available in Laravel 13.
- No fat controllers, no controller-level business logic.

## API Response Contract

```json
// SuccessResponse
{ "status": 200, "data": ..., "meta": ... }
// ProblemResponse (RFC 9457)
{ "status": 422, "title": "...", "detail": "...", "data": ... }
```

- NO `success` boolean. All datetimes `Y-m-d H:i:s`. Route names `v1.{module}.{name}`.
- Pagination: `assertPaginatedResponse()` helper; list endpoints use Filters + pagination meta.

## Auth & Authorization

- Sanctum PAT; `Authorization: Bearer {token}`; per-device tokens (list/name/revoke).
- Spatie permissions, guard-aware (`web` + `sanctum` guards seeded in tests).
- Route middleware explicit in route files (`auth:sanctum`, `permission:...`), not hidden in providers.

## Queued Work (Laravel 13.6+)

- `#[Debounce]` / `DebounceFor` attribute for debounceable jobs (last-dispatch-wins semantics) — replaces manual ShouldBeUnique hacks for high-frequency notifications.
- `Bus::bulk()` (13.13+) to batch dispatch multiple jobs for parallel processing with maxJobs.
- DTO payloads only at queue/CLI/cross-module boundaries.

## API Hardening Patterns (from research)

- Idempotency: `Idempotency-Key` header (V4 UUID), store hashed key + response in cache with TTL (24h), 422 for invalid/missing key on protected endpoints, replay returns stored response + `Idempotency-Replayed` header.
- Sunset middleware: `Sunset` header with deprecation date, optional `Link` to successor.
- `X-Request-Id`: middleware generates if absent, logs in context, returns in response.
- Localization: `Accept-Language` middleware resolves locale, 406 for unsupported.
- Security headers (X-Content-Type-Options, X-Frame-Options, etc.) + `trustedProxy` configured behind TLS.

## Testing Architecture

- Pest 5: `describe()` blocks, `it()`, datasets, custom expectations (`assertSuccessResponse`, `assertProblemResponse`, `assertPaginatedResponse`), `--agent` one-off probes.
- RefreshDatabase + role seeding + `forgetCachedPermissions()` in `beforeEach`.
- Unit tests per Action class; feature tests per CRUD op; architecture tests (ArchitectureTest.php is source of truth, DO NOT auto-fix).
- Parallel test: `php artisan test --compact --parallel` (mind Spatie cache race — see PITFALLS.md).

## Sources

- Laravel 13 changelog & docs: attributes, DebounceFor, Bus::bulk, health route JSON
- laraveldaily: PHP Attributes in Laravel 13 (36 attributes, 2026-03-18)
- GitHub: JustSteveKing/kit — API starterkit patterns (invokable controllers, Sunset, Accept-Language, OpenAPI contract tests)
- GitHub: square1-io/laravel-idempotency — idempotency key implementation
- Project KNOWLEDGE.md + AGENTS.md (module conventions, contracts, response format)
