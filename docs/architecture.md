# Modularization Architecture: Laravel Starterkit API

Status: Final. This document is the English translation of the Indonesian source of truth `ARCHITECTURE.md`. Keep both files in sync when the architecture changes.

## 1. Design Principles

Seven golden principles that underpin the entire module structure. Every rule in this document must be consistent with the following principles.

### 1.1 Modules mirror the app structure (mirror principle)

The module folder structure mirrors the stock Laravel `app/` structure, including the container folders `Http/` (Controllers, Middleware, Requests, Resources) and `Console/` (Commands) that house HTTP/CLI layers exactly like `app/Http` and `app/Console`. Adopters already familiar with the Laravel layout understand a module without additional documentation. Module-scoped concerns live inside the module; shared concerns live in `app/`. Inspiration: Nuxt Layers ("the layers structure is almost identical to a standard Nuxt application"), official Laravel package structure, and nWidart/laravel-modules.

### 1.2 Activation via config, never env (config-driven activation)

Capabilities are enabled by registering them in a config array, not through environment variables. `config/modules.php` is the central registry: the only place to manage modules (active/inactive) and their feature toggles. Inspiration: Laravel Fortify (`features` array in `config/fortify.php`), Nuxt Layers (config file as the layer marker). The central registry is an allow-list: unregistered modules are fully inert, no auto-discovery.

### 1.3 Single-responsibility actions, wired by providers

Business logic consists of action classes that each perform one business operation. Service providers are responsible for wiring: registering config, actions, and routes into the framework. Every module has one provider (`modules/{Module}/Providers/{Module}ServiceProvider.php`) that extends the abstract base `ModuleServiceProvider` (`app/Providers/`); the orchestrator `ModuleLoaderServiceProvider` loads providers of ACTIVE modules from the central registry. Inspiration: Laravel Fortify (`app/Actions/Fortify/CreateNewUser.php`), nWidart/laravel-modules (one service provider per module).

### 1.4 Self-contained modules

Migrations, factories, seeders, routes, requests, resources, and tests live inside the module. A module can be moved, deleted, or enabled without touching files outside the module (except the central registry `config/modules.php`). Inspiration: nWidart/laravel-modules.

### 1.5 Shared vocabulary lives separately in app

Contracts used across modules (interfaces, shared enums, response contracts) live in `app/`. Modules never import each other directly; they communicate through contracts. Inspiration: `shared/` in Nuxt Layers.

### 1.6 No per-module overhead

Deliberate deviation from nWidart/laravel-modules: a module does NOT have its own `composer.json`, `module.json`, `resources/assets`, or `vite.config.js`. One repo, one dependency graph, one build. Philosophy: production-ready, not overengineered.

### 1.7 Native-first escape hatch

Every customization must preserve the native Laravel path. Adopters can always skip the wrapper and use built-in APIs. Customization makes things easier, never blocks them.

## 2. Module Anatomy

### 2.1 Project folder structure

```
project/
├── app/                         # Shared code (shared vocabulary)
│   ├── Builders/                # BaseQueryBuilder (filter/sort/search/include whitelist)
│   ├── Concerns/                # Shared traits (HasDefaultBehavior, FormatDate, etc.)
│   ├── Console/
│   │   └── Commands/            # Global Artisan commands (make:module, security:check)
│   ├── Contracts/               # Cross-module interfaces (Identity)
│   ├── Enums/                   # Shared cross-module vocabulary enums (RoleEnum, PermissionEnum)
│   ├── Events/                  # Cross-module shared events (module A dispatches, module B listens)
│   ├── Features/                # Pennant class-based features used by 2+ modules
│   ├── Http/
│   │   ├── Controllers/         # Base Controller
│   │   ├── Middleware/          # Global: Sunset, TraceId, SetLocale, SecurityHeaders, Idempotency
│   │   ├── Requests/            # Common requests (PaginationRequest, BulkActionRequest)
│   │   └── Responses/           # SuccessResponse, ProblemResponse (RFC 9457)
│   ├── Jobs/                    # Queue jobs shared across modules
│   ├── Models/                  # Shared models (Sanctum PersonalAccessToken)
│   ├── Notifications/           # Shared notifications (VerifyEmail, ResetPassword)
│   ├── Payloads/                # Shared DTOs (IdempotencyPayload)
│   ├── Providers/               # AppServiceProvider, ModuleLoaderServiceProvider,
│   │                           # ModuleServiceProvider (abstract base), RouteServiceProvider
│   └── Support/                 # Global technical utilities (ProductionSecurityCheck)
├── config/                      # Global config (modules.php = central module registry)
├── database/
│   ├── factories/               # Shared factories
│   ├── migrations/              # Shared migrations
│   └── seeders/                 # Shared seeders
├── modules/
│   └── {Module}/                # One module (TitleCase folder, lowercase alias)
│       ├── Http/                # Mirrors app/Http: all HTTP layers live here
│       │   ├── Controllers/     # V1/, V2/ for API versioning (invokable single-action)
│       │   ├── Middleware/      # Module-specific middleware
│       │   ├── Requests/        # V1/ (FormRequest validation)
│       │   └── Resources/       # API resource transformers
│       ├── Console/
│       │   └── Commands/        # Module-specific Artisan commands
│       ├── Exceptions/          # Module-specific exception classes
│       ├── Features/            # Module-specific Pennant class-based features (runtime flags)
│       ├── Jobs/                # Module-specific queue jobs
│       ├── Mail/                # Module-specific mail
│       ├── Rules/               # Module-specific custom validation rules
│       ├── Events/              # Module-specific events
│       ├── Listeners/           # Module-specific listeners
│       ├── Lang/                # {locale}/ (module translations, loaded while active)
│       ├── Models/              # Module Eloquent models
│       ├── Observers/           # Model observers (registered via #[ObservedBy])
│       ├── Policies/            # Module authorization policies (registered via #[UsePolicy])
│       ├── Scopes/              # Global scopes (registered via #[ScopedBy])
│       ├── Providers/           # (required) {Module}ServiceProvider extends ModuleServiceProvider (base)
│       ├── Notifications/       # Module-specific notifications
│       ├── Actions/             # Kit-specific: one business operation, final readonly, handle()
│       ├── Builders/            # Kit-specific: query builder, extends BaseQueryBuilder
│       ├── Services/            # Kit-specific: cross-use-case logic
│       ├── Payloads/            # Kit-specific: action input DTOs
│       ├── Support/             # Kit-specific: purely technical utilities
│       ├── Contracts/           # Kit-specific: module-specific contracts
│       ├── Enums/               # Kit-specific: module-specific enums
│       ├── Config/              # Kit-specific: {alias}.php (merged by the base provider)
│       ├── Routes/              # (required) V1.php, V2.php (loaded by the base provider)
│       ├── Database/            # Kit-specific: Migrations, Factories, Seeders
│       └── Tests/               # (required) Module feature and unit tests
├── routes/
│   ├── api.php                  # Reserved; API routes are registered by modules (Routes/V1.php)
│   ├── console.php              # Console routes
│   └── web.php                  # Web routes
└── tests/                       # Global app tests
    ├── Architecture/            # Architecture tests (conventions)
    ├── Feature/                 # Infrastructure tests (middleware, responses, etc.)
    ├── Unit/                    # App unit tests
    └── Helpers.php              # Seam for module model access (not direct imports)
```

Mirror principle (1.1): `modules/{Module}/` mirrors the stock Laravel `app/` skeleton; the container folders `Http/` and `Console/` house HTTP/CLI layers exactly like `app/Http` and `app/Console`. Only 3 folders are required on ACTIVE modules: `Providers`, `Routes`, `Tests`; the rest are optional and created when needed. Kit-specific layers without a skeleton counterpart: Actions, Services, Payloads, Builders, Features, Config, Routes, Database, Tests, Lang. `app/Http/Responses` is a global contract and is not mirrored into modules.

### 2.2 Folder matrix

Required folders (must exist on ACTIVE modules):

| Folder | Content |
|---|---|
| Providers | {Module}ServiceProvider extends base ModuleServiceProvider |
| Routes | Route files (V1.php) |
| Tests | Feature and unit tests |

Optional folders (only created if they contain at least 1 file, empty folders forbidden):

| Folder | Content |
|---|---|
| Http | Controllers, Middleware, Requests, Resources (mirrors app/Http) |
| Console | Commands (mirrors app/Console) |
| Exceptions | Module-specific exception classes |
| Features | Pennant class-based features (runtime flags) |
| Jobs | Module-specific queue jobs |
| Mail | Module-specific mail |
| Rules | Module-specific custom validation rules |
| Events | Module-specific events |
| Listeners | Module-specific listeners |
| Lang | Module translations ({locale}/) |
| Models | Eloquent models |
| Observers | Model observers (via #[ObservedBy]) |
| Policies | Authorization policies (via #[UsePolicy]) |
| Scopes | Global scopes (via #[ScopedBy]) |
| Notifications | Module-specific notifications |
| Actions | Business logic, one operation per class |
| Builders | Query builders (extends BaseQueryBuilder) |
| Services | Cross-use-case logic |
| Payloads | Action input DTOs |
| Support | Purely technical utilities |
| Contracts | Module-specific contracts |
| Enums | Module-specific enums |
| Config | {alias}.php (merged by the base provider) |
| Database | Migrations, Factories, Seeders |

Inactive modules (not registered as active in the central registry) minimally contain `Providers`, `Tests`. Example: Organization. The rest of the structure appears when the module is activated.

### 2.3 Inspiration sources and deviations

| Aspect | Nuxt Layers | Laravel Fortify | nWidart/laravel-modules | Kit decision |
|---|---|---|---|---|
| Module structure | Mirrors standard app | Structured package | app/ + resources + vite | Mirrors app/ (Http/ container) |
| Activation | Config layer | Config features array | module.json + auto-discovery | Central registry config/modules.php (allow-list) |
| Module metadata | nuxt.config | - | module.json inside the module | Central registry (active, features) |
| Feature toggle | - | features array | - | Registry (build-time) + Pennant classes (runtime) |
| Business logic | Composables/utils | Actions | Service classes | Actions + Services |
| DB resources | - | Migrations via publish | Migrations/factories/seeders in module | Inside the module |
| Per-module overhead | nuxt.config | composer package | composer.json + module.json | None |
| Shared code | shared/ | Vendor namespace | Modules namespace | app/ (shared vocabulary) |
| Repositories | - | - | Repositories layer | NOT used (Eloquent is the repository) |

Decision note: the `--repository` flag was removed from the generator (Eloquent is the repository); `--event` is kept (`Events/` optional, created when needed). Executed during generator implementation.

## 3. Layer Responsibilities

Each layer: definition, rules, forbidden, example.

### 3.1 Actions

Definition: `final readonly` classes that perform ONE business operation, called by controllers, called by other actions, or used by services. Inspiration: Fortify Actions.

Rules:
1. `final readonly`, one public `handle()` method, explicitly typed parameters
2. Does NOT receive `Request`; the controller extracts data and passes it along
3. Does NOT contain HTTP logic (status codes, redirects, json)
4. Validation happens in the Request layer, not in actions
5. Every action has a unit test in `modules/*/Tests/Unit`
6. Business errors via `throw_if`/`throw_unless` + domain exceptions (`InvalidArgumentException` mapped to 422, `ModelNotFoundException` to 404 for ownership checks)
7. Interdependent multi-step writes (2+ writes) must be wrapped in `DB::transaction` or equivalent (`saveOrFail`/`deleteOrFail` for instances, `syncOrFail`/`attachOrFail` etc. for pivots); single-model writes use plain `create`/`update`/`save`/`delete`
8. NO base class/interface for Actions: the structure (`final readonly`, `handle()`) is a convention enforced by ArchitectureTest, not inheritance (principle 1.6, no per-module overhead); interfaces only when real cross-module polymorphism is needed (see 3.14)

Forbidden:
- No public methods other than `handle()`
- No HTTP dependencies (Request, Response)
- No Eloquent queries with inline domain conditions in controllers; queries live in actions or builders. Pure queries (paginate + BaseQueryBuilder filter/search/sort whitelist, without domain conditions) are allowed directly in controllers (see 3.2 rule 3)
- No HTTP helpers (`abort`, `abort_if`, `abort_unless`) in actions
- No `createOrFail` (does not exist in the framework) and no `updateOrFail`/`deleteOrFail` for lookups (they return false silently when the model does not exist)

Example:

```php
final readonly class CreateUserAction
{
    public function handle(UserPayload $payload): User
    {
        return User::create($payload->toArray());
    }
}
```

### 3.2 Controllers

Definition: `final readonly` invokable single-action classes in `modules/{Module}/Http/Controllers/V1/`. They only handle HTTP concerns: parse the request, call the action, return the response.

Rules:
1. `final readonly`, extends base `Controller`, one method `__invoke(Request|FormRequest $request): SuccessResponse`; parameters may type-hint FormRequest subclasses (example: `RegisterController`); errors are not returned by controllers but thrown as exceptions mapped by the handler to `ProblemResponse` (3.23)
2. Delegate logic to an Action via `->handle()`
3. Pure queries (paginate + BaseQueryBuilder filter/search/sort whitelist, without domain conditions) are allowed directly in controllers
4. Return type-hint `SuccessResponse` (all existing controllers are consistent, 0 usages of `JsonResponse`); `ProblemResponse` is only written by the handler
5. Follow the structure of existing sibling controllers

Forbidden:
- No queries with domain conditions in controllers (must go through an Action)
- No business logic
- No non-contract responses

Example:

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

### 3.3 Models

Definition: Eloquent models in `modules/{Module}/Models/`. Data access belongs to the module.

Rules:
1. ULID primary keys via `HasDefaultBehavior` (HasUlids + serializeDate Y-m-d H:i:s)
2. Attributes via PHP 8 attributes: `#[Fillable]`, `#[Hidden]`, `#[UseFactory]`, `#[UseEloquentBuilder]`
3. Related model registrations via attributes: `#[UsePolicy]` (policy), `#[ObservedBy]` (observer), `#[ScopedBy]` (global scope)
4. `#[Table]`, `#[UseResource]`, `#[UseResourceCollection]` only for convention deviations (non-standard table names, pivots, non-standard resource naming)
5. Cast enum columns to enum classes (`'status' => StatusEnum::class`)
6. `declare(strict_types=1)` in every file
7. Every model must have a factory
8. App-layer (tests/) accesses module models only through the `tests/Helpers.php` seam, not direct imports
9. Soft deletes use the trait `Illuminate\Database\Eloquent\SoftDeletes` (the `#[UseSoftDeletes]` attribute does not exist in Laravel 13); `withTrashed`/`onlyTrashed` queries only in actions/builders

Forbidden:
- No UUID primary keys
- No `$fillable`/`$hidden` properties
- No cross-module models

### 3.4 Services

Definition: business logic used by 2+ call sites or consolidating complex flows across use cases. Distinction from Action: Action = 1 use case; Service = shared logic.

Rules:
1. `final readonly`, dependencies injected via constructor
2. Does NOT receive `Request`
3. May call Actions and models
4. Minimum 2 call sites or a complex flow; 1 call site should be an Action

Forbidden:
- No services for a single call site
- No service calling controller/HTTP layer

Example: `UserAuthorizationService` (determines token abilities and creates the access token, used by both login and register).

### 3.5 Support

Definition: purely technical utilities, self-contained, without business state and without Eloquent dependencies.

Rules:
1. Static or `final readonly`, purely technical (crypt, formatting, technical validation)
2. If it has business logic, it is a Service; if it is 1 use case, it is an Action
3. Not called directly from controllers (via Service/Action)

Forbidden:
- No Eloquent dependencies
- No domain business logic

Example: `SocialState` (creates and verifies OAuth state tokens with expiry).

### 3.6 Builders

Definition: custom Eloquent query builders registered via `#[UseEloquentBuilder]`.

Rules:
1. `BaseQueryBuilder` is the only mechanism for filter, search, sort, include whitelists
2. Whitelist methods: `allowedSearch`, `allowedFilters`, `allowedSorts`, `allowedFields`, `allowedIncludes`
3. Models register the builder via attribute, not `newBuilder()`
4. Native Eloquent (`where`, `orderBy`, scopes) remains valid in actions/builders

Forbidden:
- No query string parsing in controllers
- No bypassing the whitelist with arbitrary parameters

Example:

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

### 3.7 Payloads

Definition: immutable `final readonly` DTOs with constructor promotion, input for actions.

Rules:
1. `final readonly`, typed properties, constructor promotion
2. Validation stays in the Request; Payloads do not validate
3. Used for data crossing layers (Request to Action, queue jobs, CLI)

Forbidden:
- No payloads with validation logic
- No mutable payloads

### 3.8 Requests

Definition: one FormRequest per endpoint in `modules/{Module}/Http/Requests/V1/`. Cross-module requests (pagination, bulk action) live in `app/Http/Requests/` (shared).

Rules:
1. One FormRequest per endpoint/action; the only exceptions are shared requests in `app/Http/Requests/` (`PaginationRequest`, `BulkActionRequest`) used across endpoints
2. Validation in `rules()`; authorization via `authorize()` or policy/permission
3. No inline validation in controllers
4. List endpoints must type-hint a `{Resource}ListRequest` in the module that extends `App\Http\Requests\PaginationRequest` (not PaginationRequest directly): the place for `authorize()` permission and extra rules for filter/sort/search; empty subclasses are allowed when only pagination is needed (existing pattern: `UserListRequest`, `RoleListRequest`, `PermissionListRequest`, `DeviceListRequest` in `modules/IAM/Requests/V1/`)
5. Request naming follows the controller: `{Resource}ListRequest` for `{Resource}ListController`

Forbidden:
- No long validation arrays in controllers
- No Request calling models directly
- No list controller type-hinting `PaginationRequest` directly from app

### 3.9 Resources

Definition: API resource transformers in `modules/{Module}/Http/Resources/`.

Rules:
1. `extends JsonResource`, contract envelope via SuccessResponse
2. Date format `Y-m-d H:i:s`
3. Resources belong to the module; app-wide shape is global

Forbidden:
- No resource altering the global envelope structure

### 3.10 Policies

Definition: per-module authorization policies, registered via `#[UsePolicy]` on the model (single source of truth, no hidden registration in providers); manual `Gate::policy` in providers is NOT used for modules.

Rules:
1. One policy per model when resource authorization exists
2. Registration via the `#[UsePolicy(Policy::class)]` attribute on the model
3. Use Spatie permission inside policies

Forbidden:
- No `Gate::policy` in module service providers
- No hidden authorization inside controllers
- No two sources of truth at once (Spatie permission OR Sanctum abilities, pick one per route)

### 3.11 Providers

Definition: `modules/{Module}/Providers/{Module}ServiceProvider.php` that wires the module into the framework. Every module provider extends the abstract base `ModuleServiceProvider` (`app/Providers/`); the orchestrator `ModuleLoaderServiceProvider` (app) loads providers of ACTIVE modules from the central registry `config/modules.php`.

Rules:
1. The base class provides loading boilerplate: merges `Config/{alias}.php`, merges `features` from the registry, loads migrations, loads routes `Routes/V1.php`, loads translations `Lang/`, registers commands `Console/Commands` (no `withCommands` in `bootstrap/app.php`; module commands are registered by the base provider)
2. Module providers are declaration-only: `moduleName()` (abstract) and the `bootModule()` hook for middleware aliases, Pennant features, bindings (policies via `#[UsePolicy]` on models)
3. `register()`/`boot()` on the base are `final`; the loading order cannot be reordered by subclasses
4. Module activation only through the central registry (allow-list); an unregistered module = its provider is never booted
5. No hidden registration; middleware aliases are registered explicitly, not magic discovery
6. The module alias is derived from `moduleName()` via `Str::snake()` (`'Media'` to `'media'`); the alias is used for config keys (`config('media.*')`), the `Config/{alias}.php` merge, and the route prefix (`api/v1/{module}`, 3.18)

Forbidden:
- No module provider extending `ServiceProvider` directly (must extend base `ModuleServiceProvider`)
- No provider registering routes outside `Routes/`
- No `env()` in providers

### 3.12 Middleware

Definition: module-specific middleware in `modules/{Module}/Http/Middleware/`; global middleware in `app/Http/Middleware/`.

Rules:
1. Middleware used only by specific module routes lives in the module
2. Global middleware (auth, throttle, security headers) lives in app
3. Middleware aliases are registered explicitly, not magic discovery

Forbidden:
- No global middleware inside modules
- No middleware without an alias

### 3.13 Enums

Definition: module-specific enums in `modules/{Module}/Enums/`; shared vocabulary enums (used by 2+ modules) in `app/Enums/`.

Rules:
1. Used by 1 module only: in the module. Used by 2+ modules: in app
2. Values in TitleCase; native labels via methods (no third-party label library)
3. Cast models to enum classes

Forbidden:
- No module-specific enum living in app/Enums
- No shared enum living in a module

### 3.14 Contracts

Definition: module contracts in `modules/{Module}/Contracts/`; cross-module contracts (shared vocabulary) in `app/Contracts/`.

Rules:
1. Modules communicate through contracts or public API seams, not by importing other modules' internal classes
2. `app/Contracts` is only for interfaces used by 2+ modules or by core
3. Eloquent models and contracts are a module's public API seam: they may be imported directly by other modules (existing example: the Media module imports `Modules\IAM\Models\User`, `Role`, `Permission`); internal classes (Actions, Services, Payloads, Support, Builders, Enums) are forbidden

Forbidden:
- No importing internal classes across modules (Actions, Services, Payloads, Support, Builders, Enums); model + contract imports are allowed (public API seam, rule 3)

Inter-module communication mechanisms (4 paths, most preferred first):

1. Shared vocabulary in `app/`: shared enums, contracts, shared requests, response contracts used by 2+ modules without cross-module imports
2. Public API seam: other modules' models + contracts may be imported directly - data + Eloquent relations (example: `Media::uploadedBy()` imports `Modules\IAM\Models\User`), authorization (`MediaPolicy` type-hints `User` + `App\Enums\PermissionEnum`), seeding (`MediaSeeder` firstOrCreate IAM `Role`/`Permission`)
3. Contracts for cross-module behavior: interfaces in `app/Contracts/` implemented by the owning module and bound in its provider (example: `Identity` abstracts the auth actor)
4. Event pub/sub for loose coupling: shared event classes in `app/Events/` (module A dispatches), listeners in the listening module registered explicitly in `bootModule()` (3.21); global listeners in `app/Listeners` are auto-discovered

When model directly vs interface:

- Data + Eloquent relations: model directly (Eloquent needs concrete classes, `belongsTo(User::class)`); interfaces cannot be used for relations
- Behavior/decoupling/2+ possible implementations: interface in `app/Contracts/` (example: `Identity`)
- Exactly 1 implementation that will never become 2+: model directly is enough; interface = YAGNI

Rule of thumb for base class/interface per layer: only if (1) there is logic executed together, (2) real polymorphism/decoupling is needed, (3) cross-module contract, (4) container binding. Forbidden for mere "consistency"; structure conventions are enforced by ArchitectureTest, not inheritance.

### 3.15 Config

Definition: global config in `config/`; module config in `modules/{Module}/Config/{alias}.php` (lowercase alias from the central registry, not the TitleCase folder name).

Rules:
1. Module config is merged by the provider when the module is active
2. Config access via typed helpers (`config()->integer(...)`) to keep types intact
3. Fortify-style features array (see section 6)

Forbidden:
- No `env()` outside config files
- No module config loaded while the module is inactive

### 3.16 Notifications

Definition: notifications in `app/Notifications/` (global) or `modules/{Module}/Notifications/` (module-specific).

Rules:
1. Queue-able, via `ShouldQueue`
2. Descriptive naming (VerifyEmail, ResetPassword)

Forbidden:
- No notifications called directly in controllers (via action/service)

### 3.17 Commands

Definition: Artisan commands in `app/Console/Commands/` (global) or `modules/{Module}/Console/Commands/` (module-specific).

Rules:
1. PHP 8 attributes: `#[Signature]`, `#[Description]`, `#[Help]`, `#[Usage]`
2. `handle(): int` with an exit code
3. Module commands are registered by the base `ModuleServiceProvider` while the module is active (no `withCommands` in `bootstrap/app.php`); global commands in `app/Console/Commands` are auto-discovered

Forbidden:
- No commands without a signature attribute

### 3.18 Routes

Definition: module route files in `modules/{Module}/Routes/V1.php`, loaded by the base `ModuleServiceProvider` while the module is active (replaces central discovery in RouteServiceProvider).

Rules:
1. Base prefix `api/v1/{module}`; route name `v1.{module}.{name}`
2. Explicit middleware in the route group (auth:sanctum, throttle, permission, feature.flag)
3. Route files only loaded if the module is active

Forbidden:
- No route registration outside `Routes/`
- No hidden middleware in providers

### 3.19 Database

Definition: module schema in `modules/{Module}/Database/` (Migrations, Factories, Seeders), loaded by the base `ModuleServiceProvider` while the module is active.

Rules:
1. Enum values as column defaults (`->default(StatusEnum::Pending->value)`)
2. No chaining migration commands with && or ; (identical timestamps)
3. Factory + Seeder for every model
4. Schema changes = review gate (requires approval)
5. Module seeders are executed via `php artisan db:seed --class=Modules\{Module}\Database\Seeders\{Name}Seeder` or from `database/seeders/DatabaseSeeder`; seeders must not call other modules' seeders (dependencies are seeded sequentially by the caller, example: `MediaSeeder` does not call `IAMSeeder`)
6. Module migration rollback via `php artisan migrate:rollback --path=modules/{Module}/Database/Migrations` (without `--path`, rollback only affects the last global batch)

Forbidden:
- No schema edits without approval
- No migrations outside modules

### 3.20 Features

Definition: module feature flags. Build-time toggle: the `features` array in the central registry (`config/modules.php`). Runtime per-user: Pennant classes in `modules/{Module}/Features/` (used by 2+ modules: `app/Features/`), checked via `FeatureFlagMiddleware`.

Rules:
1. Build-time: boolean values in the registry; merged by the base provider into `config('{alias}.features')`
2. Runtime: `final class {Feature} extends Feature`, `resolve()` contains per-user logic
3. Naming: `{module}.{feature}` (e.g. `iam.self-registration`)
4. Unregistered features are considered off (default false)

Forbidden:
- No `env()` for feature toggles
- No two sources of truth (registry vs Pennant for the same thing)

### 3.21 On-demand layers (Jobs, Events, Listeners, Mail, Rules, Exceptions, Lang, Observers, Scopes)

Definition: optional folders that live in the module when needed, following Laravel conventions: Jobs (queue jobs), Events + Listeners (event bus), Mail (email), Rules (custom validation rules), Exceptions (module-specific exception classes), Lang/{locale} (module translations), Observers (model observers via `#[ObservedBy]`), Scopes (global scopes via `#[ScopedBy]`).

Rules:
1. Created only if they contain at least 1 file (empty folders forbidden)
2. `Lang/` is loaded by the base `ModuleServiceProvider` while the module is active
3. Detailed rules just follow Laravel conventions; no separate rule file per folder
4. Module listeners are NOT auto-discovered (bootstrap only scans `app/Listeners`); register listeners explicitly in `bootModule()` via `Event::listen`/`Event::subscribe`

Forbidden:
- No empty folders as placeholders

### 3.22 Bulk Actions

Definition: mass mutation endpoints (delete, restore) processing many ids at once. Shared request `App\Http\Requests\BulkActionRequest` (validates `ids` max 50 + `action`); the controller delegates to an Action; the Action executes a single bulk query.

Rules:
1. `BulkActionRequest` (shared) is mandatory for all bulk endpoints; per-action authorization via `authorize()` based on the route name
2. Bulk action = a single `whereIn` query (delete/restore), returns count
3. `Bus::bulk`/`Bus::batch` NOT used for synchronous mutations; only for heavy per-item processing that needs a queue (no use case yet; rule added when one appears)
4. Routing: `POST /{resource}/bulk/{action}`, route name `v1.{module}.{resource}.bulk.{action}`
5. Note: bulk queries do not trigger per-row model events/observers (deliberate trade-off)

Forbidden:
- No dispatching a job per item for simple delete/restore
- No query loops in controllers; loops (if any) only in Actions

### 3.23 Error Handling & Exception Helpers

Definition: errors are communicated via exceptions and mapped to `ProblemResponse` (RFC 9457) by the handler in `bootstrap/app.php`. Laravel `abort*`/`throw*` helpers are used per layer to avoid try/catch boilerplate.

Rules:
1. HTTP layer (controllers, middleware, requests): `abort`/`abort_if`/`abort_unless` for HTTP conditions (403, 404, 409); status follows the handler mapping
2. Domain layer (Action, Payload, Support): `throw_if`/`throw_unless` + domain exceptions: `InvalidArgumentException` (mapped to 422), `ModelNotFoundException` (mapped to 404, for ownership checks), custom exceptions in `Exceptions/` when a special status/type is needed
3. Exception-to-ProblemResponse mapping only in the handler; controllers do not write manual error responses
4. Error messages via translation keys `__()`, not hardcoded strings
5. Required lookups use `findOrFail`/`firstOrFail`/`valueOrFail` (throws ModelNotFoundException to 404); do not use `updateOrFail`/`deleteOrFail`/`saveOrFail` as lookup replacements (all return false silently when the model does not exist)

Forbidden:
- No `abort`/`abort_if`/`abort_unless` in the domain layer (Actions, Payloads, Support)
- No try/catch in controllers to map errors (the handler handles it)
- No hardcoded error messages in throws

## 4. Cross-Module Conventions

1. Response contract: `SuccessResponse` / `ProblemResponse` (RFC 9457), without a `success` boolean; error type from the `config/errors.php` typeKey
2. Date format for all response fields: `Y-m-d H:i:s`
3. `declare(strict_types=1)` in every PHP file
4. PHP 8 attributes preferred over properties (models, jobs, commands)
5. Route name `v1.{module}.{name}`; module lowercase in the central registry
6. Operational classes: `final readonly`; use constructor property promotion
7. Documents (docs, rules, roadmap): pure ASCII, no emoji, no em/en dash, no arrows, use hyphens
8. Code and comment language: English

## 5. Module Lifecycle

### 5.1 Creating a module

```bash
php artisan make:module Blog
```

The generator creates the required structure: `Providers`, `Routes`, `Tests`. Optional layers are added when needed (not upfront).

### 5.2 Activating a module

The central registry `config/modules.php` is the only place to manage modules and their features:

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

`ModuleLoaderServiceProvider` reads the registry: entries with `active => true` load the module provider by convention `Modules\{Name}\Providers\{Name}ServiceProvider` (guarded by `class_exists`; an absent module folder is safe, not fatal). The base `ModuleServiceProvider` merges config + features, then loads the module migrations, routes, and translations. An unregistered module is fully inert: provider, config, migrations, routes are not loaded (proven by tests).

After changing the registry, run `php artisan config:cache` (+ `route:cache` when routes are cached) for the change to take effect; in production the registry is baked into the cache, forgetting to refresh = the module stays in its previous state.

Boot order between modules = declaration order in the central registry; the `priority` key is not used until a real cross-module boot dependency exists.

### 5.3 Deactivating a module

Remove it from the registry with `active => false` (or delete the entry). Module data stays in the database (migrations are not rolled back automatically); the schema remains, behavior is off.

### 5.4 Private modules

Private module folders are kept on disk + added to `.gitignore` + not registered in the central registry. Never pushed to a public repo.

### 5.5 Special case: Organization (tenancy)

Organization is a minimal inactive module (Providers, Tests) wrapping stancl/tenancy (opt-in tenancy option). Deliberate deviations:
- Tenant model uses UUID (stancl default), a deviation from the ULID rule, confined to the module
- `tenancy.php` config inside the module
- The rest of the structure grows when the module is activated (MVP 2)

### 5.6 Deleting a module

Delete the module folder and the central registry entry in `config/modules.php`. The provider is not booted (guarded by `class_exists`); an absent folder is not fatal. Database data remains (migrations are not auto-rolled-back).

## 6. Toggle & Native-First

### 6.1 3-level toggle model

| Level | Mechanism | Timing | Example |
|---|---|---|---|
| Module | Central registry `config/modules.php` (`active`) | Build-time | `organization` off = tenancy inert |
| Feature (static) | `features` array in the registry per module (Fortify-style) | Build-time | Media: upload vs signedUrl |
| Feature (runtime) | Pennant flags (classes in `Features/`) + FeatureFlagMiddleware | Runtime, per-user | beta flag, gradual rollout |

### 6.2 Draft code: central registry

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

The base `ModuleServiceProvider` merges `features` into `config('media.features')` at boot; the module provider is just declaration + hook:

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

### 6.3 Pennant classes (runtime, per-user)

Features that need a runtime decision (per user, gradual rollout) are defined as Pennant classes in `modules/{Module}/Features/`:

```php
// modules/Media/Features/MediaUpload.php
final class MediaUpload extends Feature
{
    public function resolve(User $user): bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin); // per-user runtime decision
    }
}
```

Routes are protected by the `feature.flag` middleware (FeatureFlagMiddleware). Features used by 2+ modules live in `app/Features/`.

Note: Pennant classes are only for runtime decisions (per-user, gradual rollout); static toggles just use the features array in the registry (6.1/6.2) without a Pennant class.

### 6.4 Chisel markers

The `/* @chisel-{feature} */` and `/* @end-chisel-{feature} */` pattern (from the vue-starter-kit Laravel) is DEFERRED: the decision follows the evaluation of `laravel/chisel` (backlog). Not adopted yet.

### 6.5 Native-first

Every wrapper must have a documented native escape hatch:
- BaseQueryBuilder: actions may still use plain `User::where(...)`
- Responses: the handler still maps exceptions to problem details
- Middleware: routes may omit special middleware when not needed
Evidence: tests proving the native path still works.

## 7. Testing

1. Placement: module tests in `modules/*/Tests/` (Feature, Unit); app tests in `tests/`
2. App-layer only accesses module models through the `tests/Helpers.php` seam; direct `Modules\*` imports in `tests/` (outside module folders) are forbidden, EXCEPT Seeder imports for test seeding needs (`$this->seed(\Modules\IAM\Database\Seeders\IAMSeeder::class)`)
3. Group per module: `->group('module:{name}')`
4. Every code change requires tests; 100% code and type coverage (quality gate)
5. ArchitectureTest (tests/Architecture/ArchitectureTest.php) is the single source of truth for conventions; assertion changes require human approval (report first, do not auto-fix)
6. Quality gates: `composer lint`, `composer types:check`, `composer test:quality`, `composer ci:check`

## 8. Mapping to Rules

This document is split into `.ai/rules/` (standard format: frontmatter paths + Goal + Rules + Forbidden + Example) as derived, enforced rules. 25 rule files exist; the mapping below is a living mapping that must stay in sync if this document changes:

| Section | Rule file |
|---|---|
| 2 (anatomy) | .ai/rules/modules-structure.md (NEW, including on-demand layers 3.21: Jobs, Events, Listeners, Mail, Rules, Exceptions, Lang, Observers, Scopes) |
| 3.1 | .ai/rules/actions.md (refactor) |
| 3.2 | .ai/rules/controllers.md (refactor) |
| 3.3 | .ai/rules/models.md (refactor) |
| 3.4 | .ai/rules/services.md (NEW) |
| 3.5 | .ai/rules/support.md (NEW) |
| 3.6 | .ai/rules/builders.md (NEW) |
| 3.7 | .ai/rules/payloads.md (NEW) |
| 3.8 | .ai/rules/requests.md (NEW) |
| 3.9 | .ai/rules/resources.md (NEW) |
| 3.10 | .ai/rules/policies.md (NEW) |
| 3.11 | .ai/rules/providers.md (refactor) |
| 3.12 | .ai/rules/middleware.md (NEW) |
| 3.13 | .ai/rules/enums.md (NEW) |
| 3.14 | .ai/rules/contracts.md (NEW) |
| 3.15 | .ai/rules/config.md (NEW) |
| 3.16 | .ai/rules/notifications.md (NEW) |
| 3.17 | .ai/rules/commands.md (NEW) |
| 3.18 | .ai/rules/routes.md (refactor) |
| 3.19 | .ai/rules/database.md (refactor) |
| 3.20 + 6 | .ai/rules/features.md (NEW) |
| 3.22 | .ai/rules/bulk-actions.md (NEW) |
| 3.23 | .ai/rules/error-handling.md (NEW) |
| 4 | .ai/rules/responses.md (refactor) + index.md |
| 7 | .ai/rules/tests.md (refactor) |

## 9. Open Questions (for review)

1. Migrating existing module folders (IAM, Media) to the new anatomy (`Http/Controllers`, `Http/Requests`, `Http/Resources`, `Console/Commands`) is a breaking change: executed in the next phase, not part of this document's review?
2. API versioning V2 is undefined yet: the anatomy mentions `V1/`, `V2/` in Controllers/Requests and `Routes/V2.php`, but rule 3.18 only defines `api/v1/{module}`. The V2 mechanism (header vs URL, V1 deprecation policy) is deferred until the first V2 use case appears?
