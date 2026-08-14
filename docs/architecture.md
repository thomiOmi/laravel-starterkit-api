# Modularization Architecture: Laravel Starterkit API

Status: **Final**. This document is the restructured, enhanced derivative of the root `ARCHITECTURE.md` (Bahasa Indonesia, source of truth). `ARCHITECTURE.md` and `.ai/rules/` must stay in sync with this file. All decisions are explained directly here.

---

## 1. Executive Architecture Overview & Design Patterns

**This architecture** is a modular Laravel starterkit: every business capability lives inside one self-contained module, enabled through a **central registry** (`config/modules.php`), and wires itself to the framework through one **service provider** per module.

**Three core ideas:**

- **Modules mirror `app/`** - adopters familiar with the Laravel layout understand a module without extra documentation.
- **Config-driven activation, no env** - no `MODULES_*` env overrides; active status and feature toggles are reviewable code decisions.
- **Native-first** - every wrapper keeps an escape hatch to the built-in Laravel API.

### Architecture Overview

```mermaid
flowchart TB
    subgraph Client["Client"]
        R["HTTP Request /api/v1/*"]
    end

    subgraph Kernel["Kernel (app/)"]
        Registry["config/modules.php (central registry)"]
        Loader["ModuleLoaderServiceProvider"]
        Base["ModuleServiceProvider (abstract base)"]
        Resp["SuccessResponse / ProblemResponse (RFC 9457)"]
    end

    subgraph Mod["Active module (e.g. IAM, Media)"]
        P["{Module}ServiceProvider"]
        RT["Routes/V1.php"]
        C["Controllers/V1/ (invokable)"]
        A["Actions (1 business operation)"]
        M["Models"]
        R["Resources"]
    end

    R --> RT --> C
    C --> A --> M
    C --> R --> Resp
    Registry --> Loader
    Loader -- "active = true" --> P
    P --> Base
    Base -- "merge config + features, load migration/route/lang" --> M
```

### 1.1 Modules mirror the app structure (mirror principle)

The module folder structure mirrors the built-in `app/` structure, including the container folders `Http/` (Controllers, Middleware, Requests, Resources) and `Console/` (Commands) that host HTTP/CLI layers exactly like `app/Http` and `app/Console`.

- **Module-scoped** things live inside the module.
- **Shared** things live in `app/`.
- Inspiration: Nuxt Layers ("the layers structure is almost identical to a standard Nuxt application"), official Laravel package structure, nWidart/laravel-modules.

### 1.2 Config-driven activation, no env

Capabilities are enabled by registering them in a config array, **not** environment variables.

- `config/modules.php` is the **central registry**: the single place to manage modules (active/inactive) and their feature toggles.
- The central registry is an **allow-list**: unregistered modules are fully inert, no auto-discovery.
- Inspiration: Laravel Fortify (`features` array in `config/fortify.php`), Nuxt Layers (config file as the layer marker).

### 1.3 Single-responsibility actions, wired by providers

- Business logic is an **action class** performing one business operation.
- The **service provider** does the wiring: registers config, actions, and routes to the framework.
- Every module has one provider (`modules/{Module}/Providers/{Module}ServiceProvider.php`) extending the abstract base `ModuleServiceProvider` (`app/Providers/`).
- The `ModuleLoaderServiceProvider` orchestrator loads providers of **ACTIVE** modules from the central registry.
- Inspiration: Laravel Fortify (`app/Actions/Fortify/CreateNewUser.php`), nWidart/laravel-modules (one service provider per module).

### 1.4 Self-contained modules

- Migrations, factories, seeders, routes, requests, resources, and tests live inside the module.
- A module can be moved, deleted, or enabled **without touching files outside the module** (except the central registry `config/modules.php`).
- Inspiration: nWidart/laravel-modules.

### 1.5 Shared vocabulary lives in app

- Contracts used across modules (interfaces, shared enums, response contract) live in `app/`.
- Modules do **not** import each other directly; they communicate through contracts.
- Inspiration: `shared/` in Nuxt Layers.

### 1.6 No per-module overhead

Deliberate deviation from nWidart/laravel-modules: modules do **NOT** have their own `composer.json`, `module.json`, `resources/assets`, or `vite.config.js`.

- **One repo, one dependency graph, one build.**
- Philosophy: production-ready, not overengineered.

### 1.7 Native-first escape hatch

- Every customization must preserve the native Laravel path.
- Adopters can always skip the wrapper and use the built-in API.
- Customizations make things easier, never block them.

---

## 2. Request Lifecycle & Data Flow

### 2.1 Successful request flow

```mermaid
sequenceDiagram
    participant Client
    participant MW as Middleware (auth, throttle, feature.flag)
    participant C as Controller (invokable, V1/)
    participant A as Action (final readonly)
    participant S as Service / Builder (optional)
    participant M as Model
    participant R as Resource
    participant Resp as SuccessResponse

    Client->>MW: HTTP request /api/v1/{module}/...
    MW->>MW: validate auth, throttle, feature flag
    MW->>C: request passes
    C->>C: parse & validate (FormRequest)
    C->>A: handle(Payload)
    A->>S: cross use-case logic (optional)
    A->>M: query / write (Eloquent)
    M-->>A: result
    A-->>C: result
    C->>R: UserResource::make(...)
    R->>Resp: SuccessResponse (data, meta)
    Resp-->>Client: 200 JSON (contract)
```

### 2.2 Error flow

```mermaid
sequenceDiagram
    participant C as Controller / Action
    participant Ex as Domain exception
    participant H as Handler (bootstrap/app.php)
    participant P as ProblemResponse
    participant Client

    C->>Ex: throw_if / throw_unless (domain), abort_* (HTTP)
    Ex->>H: exception thrown
    H->>H: map to typeKey (config/errors.php)
    H->>P: ProblemResponse (RFC 9457)
    P-->>Client: 4xx/5xx JSON
```

**Key rule:** the controller **never** writes error responses manually; all errors go through exceptions that the handler maps to `ProblemResponse`.

### 2.3 Module boot lifecycle

```mermaid
flowchart LR
    A["config/modules.php (registry)"] --> B{ModuleLoaderServiceProvider}
    B -- "active = true" --> C["Register {Module}ServiceProvider (class_exists guard)"]
    B -- "active = false / not registered" --> D["Inert: provider, config, migration, route not loaded"]
    C --> E["Base ModuleServiceProvider (final register/boot)"]
    E --> F["Merge Config/{alias}.php"]
    E --> G["Merge features from registry into config('{alias}.features')"]
    E --> H["Load migrations (modules/{Module}/Database)"]
    E --> I["Load Routes/V1.php (prefix api/{version})"]
    E --> J["Load Lang/{locale}"]
    E --> K["Register Console/Commands of module"]
    E --> L["bootModule() hook (middleware alias, feature, binding)"]
```

### 2.4 Inter-module communication (4 paths)

```mermaid
flowchart TB
    A["Module A"] --> S["1. Shared vocabulary in app/ (enum, contract, request, response)"]
    A --> P["2. Public API seam: import model + contract of another module"]
    A --> I["3. Interface in app/Contracts/ + binding in provider"]
    A --> E["4. Event pub/sub in app/Events/"]
    E --> L["Listener in module, registered explicitly in bootModule()"]
```

---

## 3. Directory Structure & Component Responsibilities

### 3.1 Project folder structure

```text
project/
├── app/                         # Shared code (shared vocabulary)
│   ├── Builders/                # BaseQueryBuilder (filter/sort/search/include whitelist)
│   ├── Concerns/                # Shared traits (HasDefaultBehavior, FormatDate, etc.)
│   ├── Console/
│   │   └── Commands/            # Global Artisan commands (make:module, security:check)
│   ├── Contracts/               # Cross-module interfaces (Identity)
│   ├── Enums/                   # Shared vocabulary enums (RoleEnum, PermissionEnum)
│   ├── Events/                  # Cross-module events (module A dispatches, module B listens)
│   ├── Features/                # Pennant class-based features used by 2+ modules
│   ├── Http/
│   │   ├── Controllers/         # Base Controller
│   │   ├── Middleware/          # Global: Sunset, TraceId, SetLocale, SecurityHeaders, Idempotency
│   │   ├── Requests/            # Shared requests (PaginationRequest, BulkActionRequest)
│   │   └── Responses/           # SuccessResponse, ProblemResponse (RFC 9457)
│   ├── Jobs/                    # Queue jobs shared across modules
│   ├── Models/                  # Shared models (Sanctum PersonalAccessToken)
│   ├── Notifications/           # Shared notifications (VerifyEmail, ResetPassword)
│   ├── Payloads/                # Shared DTOs (IdempotencyPayload)
│   ├── Providers/               # AppServiceProvider, ModuleLoaderServiceProvider,
│   │                            # ModuleServiceProvider (abstract base), RouteServiceProvider
│   └── Support/                 # Global technical utilities (ProductionSecurityCheck)
├── config/                      # Global config (modules.php = central registry)
├── database/
│   ├── factories/               # Shared factories
│   ├── migrations/              # Shared migrations
│   └── seeders/                 # Shared seeders
├── modules/
│   └── {Module}/                # One module (TitleCase folder, lowercase alias)
│       ├── Http/                # Mirrors app/Http: all HTTP layers here
│       │   ├── Controllers/     # V1/, V2/ for API versioning (invokable single-action)
│       │   ├── Middleware/      # Module-specific middleware
│       │   ├── Requests/        # V1/ (FormRequest validation)
│       │   └── Resources/       # API resource transformers
│       ├── Console/
│       │   └── Commands/        # Module-specific Artisan commands
│       ├── Exceptions/          # Module-specific exception classes
│       ├── Features/            # Module-specific Pennant class-based features (runtime flag)
│       ├── Jobs/                # Module-specific queue jobs
│       ├── Mail/                # Module-specific mail
│       ├── Rules/               # Module-specific custom validation rules
│       ├── Events/              # Module-specific events
│       ├── Listeners/           # Module-specific listeners
│       ├── Lang/                # {locale}/ (module translations, loaded when active)
│       ├── Models/              # Module Eloquent models
│       ├── Observers/           # Model observers (registered via #[ObservedBy])
│       ├── Policies/            # Module authorization policies (registered via #[UsePolicy])
│       ├── Scopes/              # Global scopes (registered via #[ScopedBy])
│       ├── Providers/           # (required) {Module}ServiceProvider extends ModuleServiceProvider (base)
│       ├── Notifications/       # Module-specific notifications
│       ├── Actions/             # Kit-specific: one business operation, final readonly, handle()
│       ├── Builders/            # Kit-specific: query builder, extends BaseQueryBuilder
│       ├── Services/            # Kit-specific: cross use-case logic
│       ├── Payloads/            # Kit-specific: action input DTOs
│       ├── Support/             # Kit-specific: pure technical utilities
│       ├── Contracts/           # Kit-specific: module-specific contracts
│       ├── Enums/               # Kit-specific: module-specific enums
│       ├── Config/              # Kit-specific: {alias}.php (merged by base provider)
│       ├── Routes/              # (required) V1.php, V2.php (loaded by base provider)
│       ├── Database/            # Kit-specific: Migrations, Factories, Seeders
│       └── Tests/               # (required) Module feature and unit tests
├── routes/
│   ├── api.php                  # Reserved; API routes registered by modules (Routes/V1.php)
│   ├── console.php              # Console routes
│   └── web.php                  # Web routes
└── tests/                       # Global app tests
    ├── Architecture/            # Architecture tests (conventions)
    ├── Feature/                 # Infrastructure tests (middleware, responses, etc.)
    ├── Unit/                    # App unit tests
    └── Helpers.php              # Seam for accessing module models (not direct imports)
```

**Mirror principle (1.1):** `modules/{Module}/` is a mirror of the built-in `app/` skeleton; the container folders `Http/` and `Console/` host HTTP/CLI layers exactly like `app/Http` and `app/Console`.

- Only 3 folders are **required** on an ACTIVE module: `Providers`, `Routes`, `Tests`.
- The rest are **optional**, created when needed.
- Kit-specific layers without a skeleton counterpart: Actions, Services, Payloads, Builders, Features, Config, Routes, Database, Tests, Lang.
- `app/Http/Responses` is a global contract and is **not mirrored** into modules.

### 3.2 Folder matrix

**Required folders (present on ACTIVE modules):**

| Folder | Contents |
|---|---|
| Providers | `{Module}ServiceProvider` extends base `ModuleServiceProvider` |
| Routes | Route file (`V1.php`) |
| Tests | Feature and unit tests |

**Optional folders (only created if they contain at least 1 file; empty folders forbidden):**

| Folder | Contents |
|---|---|
| Http | Controllers, Middleware, Requests, Resources (mirror `app/Http`) |
| Console | Commands (mirror `app/Console`) |
| Exceptions | Module-specific exception classes |
| Features | Pennant class-based features (runtime flag) |
| Jobs | Module-specific queue jobs |
| Mail | Module-specific mail |
| Rules | Module-specific custom validation rules |
| Events | Module-specific events |
| Listeners | Module-specific listeners |
| Lang | Module translations (`{locale}/`) |
| Models | Eloquent models |
| Observers | Model observers (via `#[ObservedBy]`) |
| Policies | Authorization policies (via `#[UsePolicy]`) |
| Scopes | Global scopes (via `#[ScopedBy]`) |
| Notifications | Module-specific notifications |
| Actions | Business logic, one operation per class |
| Builders | Query builders (extends `BaseQueryBuilder`) |
| Services | Cross use-case logic |
| Payloads | Action input DTOs |
| Support | Pure technical utilities |
| Contracts | Module-specific contracts |
| Enums | Module-specific enums |
| Config | `{alias}.php` (merged by base provider) |
| Database | Migrations, Factories, Seeders |

**Inactive modules** (not registered as active in the central registry) minimally contain `Providers`, `Tests`. Example: `Organization`. The rest of the structure appears when the module is activated.

### 3.3 Inspiration and deviations

| Aspect | Nuxt Layers | Laravel Fortify | nWidart/laravel-modules | Kit decision |
|---|---|---|---|---|
| Module structure | Mirror standard app | Structured package | app/ + resources + vite | Mirror app/ (`Http/` container) |
| Activation | Config layer | Config features array | module.json + auto-discovery | Central registry config/modules.php (allow-list) |
| Module metadata | nuxt.config | - | module.json inside module | Central registry (active, features) |
| Feature toggle | - | features array | - | Registry (build-time) + Pennant class (runtime) |
| Business logic | Composables/utils | Actions | Service classes | Actions + Services |
| DB resources | - | Migrations via publish | Migrations/factories/seeders in module | Inside the module |
| Per-module overhead | nuxt.config | composer package | composer.json + module.json | None |
| Shared code | shared/ | Vendor namespace | Modules namespace | app/ (shared vocabulary) |
| Repositories | - | - | Repositories layer | NOT used (Eloquent is the repository) |

**Decision note:** the `--repository` flag was removed from the generator (Eloquent is the repository); `--event` is kept (`Events/` optional, created when needed). Executed during generator implementation.

---

## 4. Strict Boundaries & Guardrails

### 4.1 Module isolation & dependencies

**Mandatory rules:**

1. Modules communicate through **contracts or the public API seam**, not imports of another module's internal classes.
2. `app/Contracts` is only for interfaces used by **2+ modules** or core.
3. Eloquent models and contracts = the module's **public API seam**: they may be imported directly by other modules (existing example: Media module imports `Modules\IAM\Models\User`, `Role`, `Permission`).
4. Internal classes (Actions, Services, Payloads, Support, Builders, Enums) **must not** be imported across modules.

**Four inter-module communication paths (most preferred first):**

1. **Shared vocabulary in `app/`**: shared enums, contracts, shared requests, response contract used by 2+ modules without cross-module imports.
2. **Public API seam**: models + contracts of another module may be imported directly - Eloquent data + relations (example: `Media::uploadedBy()` imports `Modules\IAM\Models\User`), authorization (`MediaPolicy` type-hints `User` + `App\Enums\PermissionEnum`), seeders (`MediaSeeder` firstOrCreate `Role`/`Permission` from IAM).
3. **Contract for cross-module behavior**: interface in `app/Contracts/` implemented by the owning module and bound in the provider (example: `Identity` abstracts the auth actor).
4. **Event pub/sub for loose coupling**: shared event classes in `app/Events/` (module A dispatches), listeners in the listening module registered explicitly in `bootModule()`; global listeners in `app/Listeners` are auto-discovered.

**Direct model vs interface:**

- Eloquent data + relations: **direct model** (Eloquent needs a concrete class, `belongsTo(User::class)`); interfaces cannot be used for relations.
- Behavior/decoupling/2+ possible implementations: **interface** in `app/Contracts/` (example: `Identity`).
- Exactly 1 implementation, guaranteed not to become 2+: **direct model** is enough; an interface is YAGNI.

**Base class/interface rule of thumb per layer:** only if (1) there is logic executed together, (2) real polymorphism/decoupling is needed, (3) it is a cross-module contract, (4) container binding. Forbidden for the sake of "consistency" alone; structural conventions are enforced by ArchitectureTest, not inheritance.

### 4.2 Database rules

1. Enum values as column defaults (`->default(StatusEnum::Pending->value)`).
2. **Forbidden** to chain migration commands with `&&` or `;` (identical timestamps).
3. Factory + Seeder for every model.
4. Schema changes = **review gate** (requires approval).
5. Module seeders are executed via `php artisan db:seed --class=Modules\{Module}\Database\Seeders\{Name}Seeder` or from `database/seeders/DatabaseSeeder`; **forbidden** for a seeder to call another module's seeder (dependencies are seeded sequentially by the caller, example: `MediaSeeder` does not call `IAMSeeder`).
6. Module migration rollback via `php artisan migrate:rollback --path=modules/{Module}/Database/Migrations` (without `--path`, rollback only targets the last global batch).

**Forbidden:** editing schema without approval; migrations outside a module.

### 4.3 Error handling & exception helpers

**Definition:** errors are communicated via exceptions and mapped to `ProblemResponse` (RFC 9457) by the handler in `bootstrap/app.php`. Laravel's `abort*`/`throw*` helpers are used per layer to avoid try/catch boilerplate.

| Layer | Helper | Exception / status |
|---|---|---|
| HTTP (controller, middleware, request) | `abort`/`abort_if`/`abort_unless` | HTTP conditions (403, 404, 409) |
| Domain (Action, Payload, Support) | `throw_if`/`throw_unless` | `InvalidArgumentException` (422), `ModelNotFoundException` (404, ownership checks), custom exceptions in `Exceptions/` when a specific status/type is needed |

**Rules:**

1. Exception-to-`ProblemResponse` mapping happens only in the handler; the controller **never** writes error responses manually.
2. Error messages via translation keys `__()`, not hardcoded strings.
3. Lookups that must exist use `findOrFail`/`firstOrFail`/`valueOrFail` (throws `ModelNotFoundException` to 404); **do not** use `updateOrFail`/`deleteOrFail`/`saveOrFail` as lookup substitutes (they all silently return false when the model does not exist).

**Forbidden:** `abort*` in the domain layer; try/catch in controllers to map errors; hardcoded error messages in throws.

### 4.4 Cross-module conventions

1. **Response contract**: `SuccessResponse` / `ProblemResponse` (RFC 9457), no `success` boolean; error type from `config/errors.php` typeKey.
2. **Date format** for all response fields: `Y-m-d H:i:s`.
3. **`declare(strict_types=1)`** in every PHP file.
4. **PHP 8 attributes** preferred over properties (model, job, command).
5. **Route naming** `v1.{module}.{name}`; module lowercase in the central registry.
6. **Operational classes**: `final readonly`; use constructor property promotion.
7. **Documents** (docs, rules, roadmap): pure ASCII, no emoji, no em/en dash, no arrows, use hyphens.
8. **Code and comment language**: English.

---

## 5. Layer Responsibilities

Each layer: definition, rules, forbidden, example.

### 5.1 Actions

**Definition:** `final readonly` classes performing ONE business operation, called by controllers, other actions, or services. Inspiration: Fortify Actions.

**Rules:**

1. `final readonly`, one public `handle()` method, explicit parameter types.
2. Does **NOT** accept `Request`; the controller extracts data and passes it on.
3. Does **NOT** write HTTP logic (status codes, redirects, json).
4. Validation happens in the Request layer, not in the action.
5. Every action has a unit test in `modules/*/Tests/Unit`.
6. Business errors via `throw_if`/`throw_unless` + domain exceptions (`InvalidArgumentException` mapped to 422, `ModelNotFoundException` to 404 for ownership checks).
7. Multi-step related writes (2+ writes) **must** be wrapped in `DB::transaction` or equivalent (`saveOrFail`/`deleteOrFail` for instances, `syncOrFail`/`attachOrFail` etc. for pivots); single-model writes use plain `create`/`update`/`save`/`delete`.
8. **NO** base class/interface for Actions: the structure (`final readonly`, `handle()`) is a convention enforced by ArchitectureTest, not inheritance (principle 1.6); interfaces only for real cross-module polymorphism.

**Forbidden:**

- Public methods other than `handle()`.
- HTTP dependencies (Request, Response).
- Eloquent queries with inline domain conditions in controllers; queries live in actions or builders. Pure queries (paginate + filter/search/sort whitelist BaseQueryBuilder, no domain conditions) are allowed directly in controllers.
- HTTP helpers (`abort`, `abort_if`, `abort_unless`) in actions.
- `createOrFail` (does not exist in the framework) and `updateOrFail`/`deleteOrFail` as lookups (silently return false when the model does not exist).

**Example:**

```php
final readonly class CreateUserAction
{
    public function handle(UserPayload $payload): User
    {
        return User::create($payload->toArray());
    }
}
```

### 5.2 Controllers

**Definition:** `final readonly` invokable single-action classes in `modules/{Module}/Http/Controllers/V1/`. They only handle HTTP concerns: parse the request, call the action, return the response.

**Rules:**

1. `final readonly`, extend the base `Controller`, one `__invoke(Request|FormRequest $request): SuccessResponse` method; the parameter may type-hint a FormRequest subclass (example: `RegisterController`); errors are not returned by the controller but thrown as exceptions mapped by the handler to `ProblemResponse`.
2. Delegate logic to an Action via `->handle()`.
3. Pure queries (paginate + filter/search/sort whitelist BaseQueryBuilder, no domain conditions) are allowed directly in the controller.
4. Return type-hint `SuccessResponse` (all existing controllers are consistent, 0 uses of `JsonResponse`); `ProblemResponse` is only written by the handler.
5. Follow the structure of existing sibling controllers.

**Forbidden:** queries with domain conditions in controllers (must go through Actions); business logic; non-contract responses.

**Example:**

```php
final readonly class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): SuccessResponse
    {
        $user = (new CreateUserAction)->handle(UserRegistrationPayload::fromRequest($request));

        return new SuccessResponse(
            data: UserResource::make($user),
            status: Response::HTTP_CREATED,
        );
    }
}
```

### 5.3 Models

**Definition:** Eloquent models in `modules/{Module}/Models/`. Data access belongs to the module.

**Rules:**

1. ULID primary key via `HasDefaultBehavior` (HasUlids + serializeDate `Y-m-d H:i:s`).
2. Attributes via PHP 8 attributes: `#[Fillable]`, `#[Hidden]`, `#[UseFactory]`, `#[UseEloquentBuilder]`.
3. Model-related registrations via attributes: `#[UsePolicy]` (policy), `#[ObservedBy]` (observer), `#[ScopedBy]` (global scope).
4. `#[Table]`, `#[UseResource]`, `#[UseResourceCollection]` only for convention deviations (non-standard table, pivot, non-standard resource naming).
5. Cast enum columns to enum classes (`'status' => StatusEnum::class`).
6. `declare(strict_types=1)` in every file.
7. A factory is required for every model.
8. The app layer (tests/) accesses module models only through the seam `tests/Helpers.php`, not direct imports.
9. Soft deletes use the `Illuminate\Database\Eloquent\SoftDeletes` trait (the `#[UseSoftDeletes]` attribute does not exist in Laravel 13); `withTrashed`/`onlyTrashed` queries only in actions/builders.

**Forbidden:** UUID primary keys; `$fillable`/`$hidden` properties; cross-module models.

### 5.4 Services

**Definition:** business logic used by 2+ call-sites or orchestrating complex flows across use cases. Difference from Action: **Action = 1 use case; Service = shared logic.**

**Rules:**

1. `final readonly`, dependencies injected via constructor.
2. Does NOT accept `Request`.
3. May call Actions and models.
4. Minimum 2 call-sites or a complex flow; a 1 call-site service must be converted to an Action.

**Forbidden:** services for 1 call-site; services calling controllers/HTTP layer.

**Example:** `UserAuthorizationService` (determines token abilities and creates the access token, used by login and register).

### 5.5 Support

**Definition:** pure technical utilities, self-contained, no business state and no Eloquent dependency.

**Rules:**

1. Static or `final readonly`, purely technical (crypt, format, technical validation).
2. If it has business logic, it is a Service; if 1 use case, it is an Action.
3. Not called directly from controllers (via Service/Action).

**Forbidden:** Eloquent dependencies; domain business logic.

**Example:** `SocialState` (creates and verifies OAuth state tokens with expiry).

### 5.6 Builders

**Definition:** custom Eloquent query builders registered via `#[UseEloquentBuilder]`.

**Rules:**

1. `BaseQueryBuilder` is the single mechanism for filter, search, sort, include whitelists.
2. Whitelist methods: `allowedSearch`, `allowedFilters`, `allowedSorts`, `allowedFields`, `allowedIncludes`.
3. Models register the builder via attribute, not `newBuilder()`.
4. Native Eloquent (`where`, `orderBy`, scopes) remains valid in actions/builders.

**Forbidden:** query-string parsing in controllers; bypassing whitelists with arbitrary parameters.

**Example:**

```php
User::query()
    ->with(['roles'])
    ->allowedSearch()
    ->allowedFilters()
    ->allowedSorts()
    ->allowedFields()
    ->allowedIncludes()
    ->paginate();
```

### 5.7 Payloads

**Definition:** immutable `final readonly` DTOs with constructor promotion, input for actions.

**Rules:**

1. `final readonly`, typed properties, constructor promotion.
2. Validation stays in Requests; Payloads do not validate.
3. Used for data across layers (Request to Action, queue jobs, CLI).

**Forbidden:** payloads with validation logic; mutable payloads.

### 5.8 Requests

**Definition:** one FormRequest per endpoint in `modules/{Module}/Http/Requests/V1/`. Cross-module requests (pagination, bulk action) live in `app/Http/Requests/` (shared).

**Rules:**

1. One FormRequest per endpoint/action; the only exceptions are shared requests in `app/Http/Requests/` (`PaginationRequest`, `BulkActionRequest`) used across endpoints.
2. Validation in the `rules()` method; authorization via `authorize()` or policy/permission.
3. No inline validation in controllers.
4. List endpoints **must** type-hint `{Resource}ListRequest` in the module extending `App\Http\Requests\PaginationRequest` (not PaginationRequest directly): the place for `authorize()` permission and extra filter/sort/search rules; an empty subclass is allowed when only pagination is needed (existing pattern: `UserListRequest`, `RoleListRequest`, `PermissionListRequest`, `DeviceListRequest` in `modules/IAM/Requests/V1/`).
5. Request naming follows the controller: `{Resource}ListRequest` for `{Resource}ListController`.

**Forbidden:** long validation arrays in controllers; Requests calling models directly; list controllers type-hinting `PaginationRequest` directly from app.

### 5.9 Resources

**Definition:** API resource transformers in `modules/{Module}/Http/Resources/`.

**Rules:**

1. `extends JsonResource`, contract envelope via SuccessResponse.
2. Date format `Y-m-d H:i:s`.
3. Resources belong to the module; the app-wide shape is global.

**Forbidden:** resources changing the global envelope structure.

### 5.10 Policies

**Definition:** per-module authorization policies, registered via `#[UsePolicy]` on the model (single source of truth, no hidden registration in providers); manual `Gate::policy` in providers is **NOT** used for modules.

**Rules:**

1. One policy per model when resource authorization exists.
2. Registration via the `#[UsePolicy(Policy::class)]` attribute on the model.
3. Use Spatie permissions inside policies.

**Forbidden:** `Gate::policy` in module service providers; hidden authorization inside controllers; two sources of truth at once (Spatie permission OR Sanctum abilities, pick one per route).

### 5.11 Providers

**Definition:** `modules/{Module}/Providers/{Module}ServiceProvider.php` wires the module to the framework. Every module provider extends the abstract base `ModuleServiceProvider` (`app/Providers/`); the `ModuleLoaderServiceProvider` orchestrator (app) loads providers of **ACTIVE** modules from the central registry `config/modules.php`.

**Rules:**

1. The base class provides loading boilerplate: merge `Config/{alias}.php`, merge `features` from the registry, load migrations, load routes `Routes/V1.php`, load translations `Lang/`, register commands `Console/Commands` (no `withCommands` in `bootstrap/app.php`; module commands are registered by the base provider).
2. Module providers only declare: `moduleName()` (abstract) and the `bootModule()` hook for middleware aliases, Pennant features, bindings (policies via `#[UsePolicy]` on models).
3. Base `register()`/`boot()` are `final`; subclass cannot reorder loading.
4. Module activation only through the central registry (allow-list); unregistered module = provider never boots. The base also has the `isModuleActive()` guard = `config()->boolean("modules.modules.{alias}.active", false)`: providers of inactive modules stay inert even if registered manually.
5. No hidden registrations; middleware aliases registered explicitly, not via magic discovery.
6. The module alias is derived from `moduleName()` via `Str::snake()` (`'Media'` becomes `'media'`); the alias is used for the config key (`config('media.*')`), `Config/{alias}.php` merge, and the route prefix (`api/v1/{module}`).
7. `OrganizationServiceProvider` is the exception: extends stancl `TenancyServiceProvider` (not the base) because tenancy is opt-in via its own provider lifecycle.

**Forbidden:** module providers extending `ServiceProvider` directly (must extend base `ModuleServiceProvider`); providers registering routes outside `Routes/`; `env()` in providers.

### 5.12 Middleware

**Definition:** module-specific middleware in `modules/{Module}/Http/Middleware/`; global middleware in `app/Http/Middleware/`.

**Rules:**

1. Middleware used only by specific module routes lives in the module.
2. Global middleware (auth, throttle, security headers) lives in app.
3. Middleware aliases registered explicitly, not via magic discovery.

**Forbidden:** global middleware inside modules; middleware without aliases.

### 5.13 Enums

**Definition:** module-specific enums in `modules/{Module}/Enums/`; shared vocabulary enums (used by 2+ modules) in `app/Enums/`.

**Rules:**

1. 1 module call-site only: in the module. 2+ modules: in app.
2. TitleCase values; native labels via methods (no label library dependency).
3. Cast models to enum classes.

**Forbidden:** module-specific enums living in `app/Enums`; shared enums living in a module.

### 5.14 Config

**Definition:** global config in `config/`; module config in `modules/{Module}/Config/{alias}.php` (lowercase alias from the central registry, not the TitleCase folder name).

**Rules:**

1. Module config is merged by the provider when the module is active.
2. Access config via typed helpers (`config()->integer(...)`) to preserve types.
3. Fortify-style features array.

**Forbidden:** `env()` outside config files; module config loaded while the module is inactive.

### 5.15 Notifications

**Definition:** notifications in `app/Notifications/` (global) or `modules/{Module}/Notifications/` (module-specific).

**Rules:** queue-able via `ShouldQueue`; descriptive naming (VerifyEmail, ResetPassword).

**Forbidden:** notifications called directly in controllers (via action/service).

### 5.16 Commands

**Definition:** Artisan commands in `app/Console/Commands/` (global) or `modules/{Module}/Console/Commands/` (module-specific).

**Rules:**

1. PHP 8 attributes: `#[Signature]`, `#[Description]`, `#[Help]`, `#[Usage]`.
2. `handle(): int` with exit code.
3. Module commands are registered by the base `ModuleServiceProvider` when the module is active (no `withCommands` in `bootstrap/app.php`); global commands in `app/Console/Commands` are auto-discovered.

**Forbidden:** commands without attribute signatures.

### 5.17 Routes

**Definition:** module route files in `modules/{Module}/Routes/V1.php`, loaded by the base `ModuleServiceProvider` when the module is active (replacing central discovery in RouteServiceProvider).

**Rules:**

1. Base prefix `api/v1/{module}`; route name `v1.{module}.{name}`.
2. Explicit middleware on route groups (auth:sanctum, throttle, permission, feature.flag).
3. Route files are only loaded when the module is active.

**Forbidden:** route registration outside `Routes/`; hidden middleware in providers.

### 5.18 Features

**Definition:** module feature flags. **Build-time toggle**: `features` array in the central registry (`config/modules.php`). **Runtime per-user**: Pennant classes in `modules/{Module}/Features/` (used by 2+ modules: `app/Features/`), checked via `FeatureFlagMiddleware`.

**Rules:**

1. Build-time: boolean values in the registry; merged by the base provider into `config('{alias}.features')`.
2. Runtime: `final class {Feature} extends Feature`, `resolve()` holds per-user logic.
3. Naming: `{module}.{feature}` (e.g. `iam.self-registration`).
4. Unregistered features are considered off (default false).

**Forbidden:** `env()` for feature toggles; two sources of truth (registry vs Pennant for the same thing).

### 5.19 Bulk actions

**Definition:** mass mutation endpoints (delete, restore) processing many ids at once. Shared request `App\Http\Requests\BulkActionRequest` (validates `ids` max 50 + `action`); the controller delegates to an Action; the Action executes one bulk query.

**Rules:**

1. `BulkActionRequest` (shared) required for all bulk endpoints; per-action authorization via `authorize()` based on route name.
2. Bulk Action = one `whereIn` query (delete/restore), returns count.
3. `Bus::bulk`/`Bus::batch` is **NOT** used for synchronous mutations; only for heavy per-item processing needing a queue (no use case yet; rule added when one appears).
4. Routing: `POST /{resource}/bulk/{action}`, route name `v1.{module}.{resource}.bulk.{action}`.
5. Note: bulk queries do not trigger model events/observers per row (deliberate trade-off).

**Forbidden:** dispatching one job per item for simple delete/restore; query loops in controllers (loops, when needed, only in Actions).

### 5.20 On-demand layers (Jobs, Events, Listeners, Mail, Rules, Exceptions, Lang, Observers, Scopes)

**Definition:** optional folders that live in the module when needed, following Laravel conventions: Jobs (queue jobs), Events + Listeners (event bus), Mail (email), Rules (custom validation rules), Exceptions (module-specific exception classes), `Lang/{locale}` (module translations), Observers (model observers via `#[ObservedBy]`), Scopes (global scopes via `#[ScopedBy]`).

**Rules:**

1. Created only if they contain at least 1 file (empty folders forbidden).
2. `Lang/` is loaded by the base `ModuleServiceProvider` when the module is active.
3. Detailed rules simply follow Laravel conventions; no separate rule file per folder.
4. Module listeners are NOT auto-discovered (bootstrap only scans `app/Listeners`); register listeners explicitly in `bootModule()` via `Event::listen`/`Event::subscribe`.

**Forbidden:** empty folders as placeholders.

---

## 6. Module Lifecycle

### 6.1 Creating a module

```bash
php artisan make:module Blog
```

The generator creates the required structure: `Providers`, `Routes`, `Tests`. Optional layers are added when needed (not up front).

### 6.2 Activating a module

The central registry `config/modules.php` is the single place to manage modules and their features:

```php
return [
    'modules' => [
        'iam' => [
            'active'   => true,
            'features' => [
                'register'    => true,
                'social-auth' => true,
            ],
        ],
        'media' => [
            'active'   => true,
            'features' => [
                'upload'     => true,
                'signed-url' => false,
            ],
        ],
        'organization' => [
            'active'   => false,
            'features' => [
                'multi-tenancy' => false,
            ],
        ],
    ],
];
```

**How it works:**

1. `ModuleLoaderServiceProvider` reads the registry: entries with `active => true` load the module provider via the convention `Modules\{Name}\Providers\{Name}ServiceProvider` (guarded by `class_exists`; an absent module folder is safe, not fatal).
2. The base `ModuleServiceProvider` merges config + features, then loads migrations, routes, and translations.
3. Unregistered modules are **fully inert**: provider, config, migrations, routes are not loaded (proven by tests).

**Important:** after changing the registry, run `php artisan config:cache` (+ `route:cache` when routes are cached) so the change takes effect; in production the registry is baked into the cache, forgetting to refresh keeps the module in its previous state.

**Boot order** across modules = declaration order in the central registry; the `priority` key is not used until a real cross-module boot dependency appears.

### 6.3 Deactivating a module

Remove from the registry with `active => false` (or delete the entry). Module data stays in the database (migrations are not rolled back automatically); the schema stays, behavior is off.

### 6.4 Private modules

Private module folders are stored on disk + added to `.gitignore` + not registered in the central registry. Never sent to a public repo.

### 6.5 Special case: Organization (tenancy)

Organization is a minimal inactive module (Providers, Tests) wrapping stancl/tenancy (opt-in tenancy option). Deliberate deviations:

- The tenant model uses **UUID** (stancl default), a deviation from the ULID rule, confined to the module.
- Config `tenancy.php` inside the module.
- The rest of the structure grows when the module is activated (MVP 2).

### 6.6 Deleting a module

Delete the module folder and the central registry entry in `config/modules.php`. The provider is not booted (guarded by `class_exists`); an absent folder is not fatal. Database data remains (migrations are not auto-rolled back).

---

## 7. Toggle & Native-First

### 7.1 3-level toggle model

```mermaid
flowchart TB
    L1["Level 1 - Module: central registry active flag, build-time"]
    L2["Level 2 - Static feature: features array in registry, build-time"]
    L3["Level 3 - Runtime feature: Pennant class + FeatureFlagMiddleware, per-user"]
    L1 --> L2 --> L3
```

| Level | Mechanism | Timing | Example |
|---|---|---|---|
| Module | Central registry `config/modules.php` (`active`) | Build-time | `organization` off = tenancy inert |
| Feature (static) | `features` array in the registry per module (Fortify-style) | Build-time | Media: upload vs signedUrl |
| Feature (runtime) | Pennant flags (classes in `Features/`) + FeatureFlagMiddleware | Runtime, per-user | beta flag, gradual rollout |

### 7.2 Draft code: central registry

```php
// config/modules.php
return [
    'modules' => [
        'media' => [
            'active'   => true,
            'features' => [
                'upload'     => true,
                'signed-url' => false,
            ],
        ],
    ],
];
```

The base `ModuleServiceProvider` merges `features` into `config('media.features')` at boot; the module provider only declares + hooks:

```php
// modules/Media/Providers/MediaServiceProvider.php
final class MediaServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Media';
    }

    protected function bootModule(): void
    {
        if (MediaFeatures::enabled(MediaFeatures::signedUrl())) {
            // register signed URL routes or middleware only when enabled
        }
    }
}
```

```php
// modules/Media/Support/MediaFeatures.php
final class MediaFeatures
{
    public static function upload(): string
    {
        return 'upload';
    }

    public static function signedUrl(): string
    {
        return 'signed-url';
    }

    public static function enabled(string $feature): bool
    {
        return config()->boolean("media.features.{$feature}", false);
    }
}
```

### 7.3 Pennant classes (runtime, per-user)

Features that need a runtime decision (per user, gradual rollout) are defined as Pennant classes in `modules/{Module}/Features/`:

```php
// modules/Media/Features/MediaUpload.php
final class MediaUpload extends Feature
{
    public function resolve(User $user): bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin); // runtime per-user decision
    }
}
```

Routes are protected by the `feature.flag` middleware (FeatureFlagMiddleware). Features used by 2+ modules live in `app/Features/`.

**Note:** Pennant classes are only for runtime decisions (per-user, gradual rollout); static toggles simply use the features array in the registry without a Pennant class.

### 7.4 Chisel markers

The `/* @chisel-{feature} */` and `/* @end-chisel-{feature} */` pattern (from the vue-starter-kit Laravel) is **DEFERRED**: decision follows the `laravel/chisel` evaluation (backlog). Not adopted yet.

### 7.5 Native-first

Every wrapper must have a documented native escape hatch:

- **BaseQueryBuilder**: actions may still use plain `User::where(...)`.
- **Responses**: the handler still maps exceptions to problem details.
- **Middleware**: routes may skip special middleware when not needed.

**Proof:** tests proving the native path still works.

---

## 8. Testing & Deployment Strategy

### 8.1 Testing rules

1. **Placement**: module tests in `modules/*/Tests/` (Feature, Unit); shared app tests in `tests/` (Feature, Unit, Architecture).
2. **Test folder structure** - module tests:
   ```text
   modules/{Module}/Tests/
   ├── Feature/                  # HTTP feature tests (controllers, policies, middleware)
   │   └── V1/                   # optional: mirrors Http/Controllers/V1 when versioned
   └── Unit/                     # isolated unit tests (actions, enums, builders, services)
   ```
   Shared tests:
   ```text
   tests/
   ├── Architecture/             # ArchitectureTest - single source of truth for conventions
   ├── Datasets/                 # shared named datasets (2+ uses)
   ├── Feature/                  # shared feature tests (app infrastructure, middleware, registry)
   ├── Unit/                     # shared unit tests (responses, builders, enums, notifications)
   ├── Expectations.php          # custom Pest expectations
   ├── Helpers.php               # typed helpers seam (assertSuccessResponse, loginAsUser, ...)
   ├── Pest.php                  # groups, RefreshDatabase, beforeEach
   └── TestCase.php              # base test case
   ```
3. **Supported suites (current)**: `unit`, `feature`, and `profanity`. Coverage, mutation, and type-coverage gates are temporarily suspended (scripts removed); re-enable once the suite stabilizes.
4. **Composer scripts**: `composer test` (pest, unit + feature) and `composer test:profanity` are the main testing commands. `composer test:quality`, `composer test:mutation` are removed for now.
5. **Groups**: shared tests are grouped in `tests/Pest.php` (`app`, `feature`, `unit`, `arch`); module tests tag `->group('module:{name}')` per test plus the `feature`/`unit` group from `tests/Pest.php`. Filter with `vendor/bin/pest --group=app` (shared only), `--group=feature` (all feature tests), `--group=module:iam` (IAM only). See https://pestphp.com/docs/grouping-tests
6. **Imports**: shared tests MAY import module classes directly (models, factories, seeders, contracts, enums) - ArchitectureTest allows `Tests` to use `Modules\*\*`. `tests/Helpers.php` stays as a convenience seam for shared helpers, not a hard boundary. Module tests stay self-contained in `modules/*/Tests/` and may import their own module plus other modules' public API seam (models, contracts).
7. **Writing tests**: follow the pest-testing skill: feature-first, factories over manual creation, datasets to avoid duplication, specific assertions, fakes over mocks. See https://github.com/matula/laravel-claude-marketplace/tree/main/pest-testing
8. **ArchitectureTest** (`tests/Architecture/ArchitectureTest.php`) is the single source of truth for conventions; assertion changes require human approval (report first, do not auto-fix).
9. **Quality gates**: `composer lint`, `composer rector:dry`, `composer types:check`, `composer test`, `composer test:profanity`, `composer ci:check`.

### 8.2 Quality flow (quality gates)

```mermaid
flowchart LR
    A["composer lint (pint)"] --> B["composer rector:dry (rector)"]
    B --> C["composer types:check (phpstan)"]
    C --> D["composer test (pest: unit + feature)"]
    D --> E["composer test:profanity"]
```

---

## 9. Mapping to Rules

This document is broken down into `.ai/rules/` (standard format: frontmatter paths + Goal + Rules + Forbidden + Example) as an enforced derivative. 25 rule files already exist; the mapping below is a **living mapping** that must stay in sync if this document changes.

| Section | Rule file |
|---|---|
| 3 (anatomy) | .ai/rules/modules-structure.md (includes on-demand layers: Jobs, Events, Listeners, Mail, Rules, Exceptions, Lang, Observers, Scopes) |
| 5.1 | .ai/rules/actions.md |
| 5.2 | .ai/rules/controllers.md |
| 5.3 | .ai/rules/models.md |
| 5.4 | .ai/rules/services.md |
| 5.5 | .ai/rules/support.md |
| 5.6 | .ai/rules/builders.md |
| 5.7 | .ai/rules/payloads.md |
| 5.8 | .ai/rules/requests.md |
| 5.9 | .ai/rules/resources.md |
| 5.10 | .ai/rules/policies.md |
| 5.11 | .ai/rules/providers.md |
| 5.12 | .ai/rules/middleware.md |
| 5.13 | .ai/rules/enums.md |
| 4.1 (Contracts) | .ai/rules/contracts.md |
| 5.14 | .ai/rules/config.md |
| 5.15 | .ai/rules/notifications.md |
| 5.16 | .ai/rules/commands.md |
| 5.17 | .ai/rules/routes.md |
| 4.2 (Database) | .ai/rules/database.md |
| 5.18 + 7 (Features) | .ai/rules/features.md |
| 5.19 | .ai/rules/bulk-actions.md |
| 4.3 | .ai/rules/error-handling.md |
| 4.4 | .ai/rules/responses.md + index.md |
| 8 | .ai/rules/tests.md |

---

## 10. Open Questions (for review)

1. ~~Migrating existing module folders (IAM, Media) to the new anatomy (`Http/Controllers`, `Http/Requests`, `Http/Resources`, `Console/Commands`) is a breaking change: should it be executed in the next phase instead of being part of this document's review?~~ **Resolved:** scheduled for phase P5 (Module Consistency, gate G1) — see ADR-0027.
2. API V2 versioning is undefined: the anatomy mentions `V1/`, `V2/` in Controllers/Requests and `Routes/V2.php`, but the routes rules only define `api/v1/{module}`. The V2 mechanism (header vs URL, V1 sunset policy) is **deferred** until the first V2 use case appears — see ADR-0027. Re-open when that use case lands.