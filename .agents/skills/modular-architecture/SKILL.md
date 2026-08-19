---
name: modular-architecture
description: >
  Scaffold and extend Laravel modules following the project's DDD-style
  structure with Actions, Builders, Controllers, Payloads, Requests, Resources,
  Routes, Services, and Database layers. Use when creating a new module
  ("create new module"), adding a feature ("add feature", "make a controller",
  "create action", "add a filter"), or following the project's modular
  conventions ("module pattern", "modular structure").
metadata:
  version: "1.1"
---

# Modular Architecture

## Module Directory Structure

Each module lives under `modules/{ModuleName}/` and follows this layout (lowercase root dirs, `app/` mirrors Laravel):

```
modules/{ModuleName}/
├── app/                   # Mirrors the stock Laravel app/ skeleton

│   ├── Actions/           # Single-responsibility business actions

│   ├── Builders/          # BaseQueryBuilder subclasses for list queries

│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── V1/        # Invokable single-action controllers (API v1)

│   │   ├── Middleware/    # Module-specific HTTP middleware

│   │   ├── Requests/
│   │   │   └── V1/        # FormRequest validation (API v1)

│   │   └── Resources/     # Eloquent API resources

│   ├── Models/            # Eloquent models

│   ├── Payloads/
│   │   └── V1/            # Immutable DTOs for action input (API v1)

│   ├── Providers/         # {Module}ServiceProvider + RouteServiceProvider

│   ├── Services/          # Business services (optional)

│   └── Support/           # Module-specific helpers (optional)

├── config/                # config.php merged into config('{alias}.*')

├── database/
│   ├── factories/         # Model factories

│   ├── migrations/        # Database migrations

│   └── seeders/           # Database seeders

├── routes/                # Versioned route files (V1.php, V2.php)

├── tests/
│   ├── Feature/           # Pest feature tests

│   └── Unit/              # Pest unit tests

├── composer.json          # Per-module package metadata (nWidart)

└── module.json            # nWidart module manifest (name, priority, providers)

```

## Auto-Discovery

Module registration is handled by `nwidart/laravel-modules`:

1. Each module has a `module.json` manifest; `config/modules.php` configures nWidart (FileActivator, paths, cache)
2. Live activation state lives in `modules_statuses.json` (e.g. `{"IAM": true}`); a module only boots when its entry is `true`
3. The module's `app/Providers/{ModuleName}ServiceProvider.php` extends `Nwidart\Modules\Support\ModuleServiceProvider`, which auto-merges `config/config.php`, loads `database/migrations` + `database/factories`, and registers the providers listed in the `$providers` array
4. Routes are loaded by the module's own `app/Providers/RouteServiceProvider.php` (extends `Illuminate\Foundation\Support\Providers\RouteServiceProvider`), not the app `RouteServiceProvider`

You do NOT need to register modules in `config/app.php`.

## Step: Create a New Module

1. Run `php artisan module:make {ModuleName}` -- generates `module.json`, `composer.json`, `config/config.php`, `routes/V1.php`, `app/Providers/{ModuleName}ServiceProvider.php` + `EventServiceProvider.php` + `RouteServiceProvider.php`, `app/Http/Controllers/{ModuleName}Controller.php`, `database/seeders/{ModuleName}DatabaseSeeder.php`, and `tests/`
   - `--api` generates API scaffolding, `--disabled` creates the module inactive, `--plain` skips scaffolding
   - Layer commands write into convention paths (see `docs/module-generator.md`): models -> `app/Models`, scopes -> `app/Models/Scopes`, actions/services -> `app/Actions`/`app/Services` (final readonly), helpers -> `app/Support`, interfaces -> `app/Contracts`, resources -> `app/Http/Resources`, commands -> `app/Console/Commands`, mail -> `app/Mail`
2. The scaffold already produces the versioned contract: `routes/V1.php` is mounted by the generated `RouteServiceProvider` via `config('apiroute.supported_versions')` (default `['V1']`); additional versions follow `V{number}.php` casing
3. In `app/Providers/RouteServiceProvider.php`, list `V1` (or the version) in `config('apiroute.supported_versions')` so `mapApiRoutes()` mounts it on `api/v1` with name prefix `api.v1.{alias}.`
4. Enable the module with `php artisan module:enable {ModuleName}` (writes `modules_statuses.json`)
5. Run `php artisan module:migrate` for the module's migrations (or `migrate --path=modules/{ModuleName}/database/migrations`)
6. Add a feature test asserting the generated route contract (see console-commands rule); scaffolded modules must boot and pass the architecture tests

## Step: Add a New Feature

Follow this order when adding a new CRUD feature (e.g., a new resource):

1. **Model** -- Define the Eloquent model with `#[Fillable]`, `#[Hidden]`, `#[UseFactory]` attributes
2. **Migration** -- Create the table migration
3. **Factory** -- Create the model factory
4. **Route** -- Add routes to `routes/V1.php`
5. **Controller** -- Create invokable controller
6. **Action** -- Create single-responsibility action
7. **Payload** -- Create immutable DTO (for create/update)
8. **Request** -- Create FormRequest with validation + authorization
9. **Builder** -- Extend `BaseQueryBuilder` with filter/sort/field whitelists (for list queries)
10. **Resource** -- Create API resource
11. **Unit Test** -- Test the Action in isolation
12. **Feature Test** -- Test the full HTTP flow

## Layer Conventions

### Controller

```php
final readonly class {Resource}{Action}Controller
{
    public function __construct(
        private {Action} ${action},
    ) {}

    public function __invoke({Request} $request): SuccessResponse
    {
        // ...
    }
}
```

- Namespace: `Modules\{Module}\Http\Controllers\V1`
- Must be `final readonly`
- Must have `__invoke()` method
- Must inject Action(s) via constructor
- Must return `SuccessResponse` or `ProblemResponse`
- Must not use `Illuminate\Database\Eloquent\Model` directly
- May use `#[Middleware('auth:sanctum')]` attribute (Laravel 13+) on the class or `__invoke` method as an alternative to route-defined middleware

### Action

```php
final readonly class {Action}
{
    public function handle({Payload} $payload): {Model}
    {
        // ...
    }
}
```

- Namespace: `Modules\{Module}\Actions`
- Must be `final readonly`
- Must have `handle()` method returning the appropriate type
- Must receive Payload (not Request) for mutations
- Must inject dependencies via constructor
- Must not use `Illuminate\Http\Request`

### Payload

```php
final readonly class {Resource}Payload
{
    public function __construct(
        public string $name,
        public ?string $field = null,
    ) {}

    public static function fromRequest({Request} $request): self
    {
        return new self(
            name: trim($request->string('name')->toString()),
            // ...
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            // ...
        ], fn (mixed $value) => $value !== null);
    }
}
```

- Namespace: `Modules\{Module}\Payloads\V1`
- Must be `final readonly`
- Must have `fromRequest()` static factory
- Must have `toArray()` method (filtering null for updates)
- Must not import or reference Model classes

### Builder

```php
/** @extends BaseQueryBuilder<{Model}> */
class {Resource}Builder extends BaseQueryBuilder
{
    protected array $allowedFilters = ['name', 'status'];
    protected array $allowedSorts = ['name', 'created_at'];
    protected array $allowedFields = ['id', 'name', 'created_at', 'updated_at'];
    protected array $allowedIncludes = [];
    protected array $searchableColumns = ['name'];
    protected array $exactMatchColumns = [];
}
```

- Namespace: `Modules\{Module}\Builders`
- Must extend `App\Builders\BaseQueryBuilder`
- Must whitelist `$allowedFilters`, `$allowedSorts`, `$allowedFields`; add `$allowedIncludes` and `$searchableColumns` as needed
- Registered on the model via the `#[UseEloquentBuilder]` attribute
- Chain `allowedSearch()`, `allowedFilters()`, `allowedSorts()`, `allowedFields()`, `allowedIncludes()` in list actions
- Strategy methods (camelCased filter key) and model named scopes (e.g. Spatie's `role`) are dispatched automatically

### Request

```php
final class {Resource}Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('{resource}.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // ...
        ];
    }

    public function payload(): {Resource}Payload
    {
        return {Resource}Payload::fromRequest($this);
    }
}
```

- Namespace: `Modules\{Module}\Http\Requests\V1`
- Must have `payload()` method returning a Payload
- Must implement `authorize()` with Spatie `can()` checks
- Use `PasswordValidationRules` and `ProfileValidationRules` concerns when needed
- POST vs PUT/PATCH detection via `$this->isMethod('POST')`

### Resource

```php
class {Resource}Resource extends JsonResource
{
    use FormatDate;

    public function toArray(Request $request): array
    {
        return [
            'id' => strval($this->resource->id),
            'created_at' => $this->formatDate($this->resource->created_at) ?? '',
            // ...
        ];
    }
}
```

- Namespace: `Modules\{Module}\Http\Resources`
- Must extend `JsonResource`
- Must use `App\Concerns\FormatDate` trait
- Must guard eager-loaded relations with `relationLoaded()`
- All datetime fields must use `Y-m-d H:i:s` format

### Route

`routes/V1.php` files are relative: the module's `RouteServiceProvider` mounts them on `api/{version}` with middleware `api` and name prefix `api.{version}.{alias}.`.

```php
Route::prefix('{resources}')
    ->name('{resource}.')
    ->middleware(['auth:sanctum', 'verified', 'throttle:api'])
    ->group(function () {
        Route::get('/', {Resource}ListController::class)->name('index');
        Route::post('/', {Resource}CreateController::class)->name('create');
        Route::get('/{id}', {Resource}ShowController::class)->name('show');
        Route::put('/{id}', {Resource}UpdateController::class)->name('update');
        Route::delete('/{id}', {Resource}DeleteController::class)->name('delete');
    });
```

- Final route names: `api.{version}.{module}.{name}` (prefix set in RouteServiceProvider)
- Final URL: `api/v1/{path}` -- no module segment in the URL (e.g. `/api/v1/users`, not `/api/v1/iam/users`)
- Auth routes under `auth/` prefix
- Resource routes under plural resource name prefix

### Model

```php
#[Fillable(['name', 'description'])]
#[UseFactory({Resource}Factory::class)]
class {Resource} extends Model
{
    use HasDefaultBehavior, HasFactory;
}
```

- Namespace: `Modules\{Module}\Models`
- Must use `App\Concerns\HasDefaultBehavior` trait for ULID + soft deletes
- Must use PHP 8 attributes (`#[Fillable]`, `#[Hidden]`, `#[UseFactory]`) over class properties
- Do NOT use PHP 8.4 property hooks on Eloquent models -- Eloquent's magic `__get`/`__set` handles all attribute access and hooks will not fire for database column access

### Service Provider

```php
class {Module}ServiceProvider extends ModuleServiceProvider
{
    protected string $name = '{Module}';

    protected string $nameLower = '{lower}';

    /** @var string[] */
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Route::aliasMiddleware('active', EnsureUserIsActive::class);
    }
}
```

- Namespace: `Modules\{Module}\Providers` (under `app/Providers/`)
- Must extend `Nwidart\Modules\Support\ModuleServiceProvider`
- The nWidart base merges `config/config.php`, loads `database/migrations` + `database/factories`, and registers the `$providers` array (EventServiceProvider, RouteServiceProvider)
- `boot()` is for declaration only: middleware aliases, Pennant features, bindings

### Module Route Service Provider

`app/Providers/RouteServiceProvider.php` extends `Illuminate\Foundation\Support\Providers\RouteServiceProvider`, iterates `config('apiroute.supported_versions')` (default `['V1']`), and mounts each existing `routes/{Version}.php`:

```php
protected function mapApiRoutes(): void
{
    $versions = config()->array('apiroute.supported_versions', ['V1']);

    foreach ($versions as $version) {
        $routeFile = module_path($this->name, "routes/{$version}.php");

        if (! file_exists($routeFile)) {
            continue;
        }

        Route::prefix('api/'.strtolower($version))
            ->middleware(['api'])
            ->name(strtolower($version).'.'.$this->nameLower.'.')
            ->group($routeFile);
    }
}
```

### Tests

Unit test per Action (test business logic in isolation) in `tests/Unit`. Feature test per endpoint (test the full HTTP request/response cycle) in `tests/Feature`. Use response assertion helpers: `assertSuccessResponse(status)`, `assertProblemResponse(status)`, `assertPaginatedResponse()`.

## Project-Specific Rules (Must Do)

- Return `SuccessResponse` or `ProblemResponse` from controllers (never `JsonResponse`)
- Use `payload()` method on Request to get the Payload DTO
- Extend `App\Builders\BaseQueryBuilder` for all query filtering (`allowedSearch`, `allowedFilters`, `allowedSorts`, `allowedFields`, `allowedIncludes`)
- Use `App\Concerns\FormatDate` trait on all Resources
- Use `App\Concerns\HasDefaultBehavior` trait on all Models
- Use PHP 8 attributes (`#[Fillable]`, `#[Hidden]`, `#[UseFactory]`) on Models, not class properties
- Use `config()->string()` / `config()->integer()` / `config()->boolean()` / `config()->array()` for config access
- Use `relationLoaded()` in Resources to guard against N+1
- Write Unit test per Action + Feature test per endpoint
- Use `(string)` / `(int)` / `(bool)` for type casting over function calls
- Route naming format: `api.{version}.{module}.{name}`
- Use `#[Middleware]` attribute (Laravel 13+) on controllers as an alternative to defining middleware in route groups

## Project-Specific Prohibitions (Must Do Not)

- Do not import Models in Payloads or Requests
- Do not use `Illuminate\Http\Request` in Actions
- Do not use `Config` facade in business code
- Do not use `@phpstan-ignore` comments -- fix the root cause
- Do not register modules manually in `config/app.php` -- nWidart auto-discovery handles it
- Do not use `$fillable`, `$hidden`, `$table` class properties -- use PHP attributes instead
- Do not use PHP 8.4 property hooks on Eloquent model properties -- Eloquent's magic accessor system bypasses native property hooks; use accessors/mutators instead
- Do not hand-roll module bootstrapping (custom auto-discovery providers, central registry in `config/modules.php`) -- nWidart handles activation via `modules_statuses.json` (FileActivator)

## Common Pitfalls

- Forgetting `declare(strict_types=1)` at the top of every PHP file
- Forgetting `#[UseFactory(ModelFactory::class)]` on Model
- Route file not listed in `RouteServiceProvider::mapApiRoutes()` (missing from `apiroute.supported_versions` or missing `file_exists` guard) -- module works but routes return 404
- Forgetting `module:enable` after scaffolding -- module exists but never boots
- `toArray()` in Payload does not filter null values -- causes SQL errors on update when password is not provided
- `authorize()` does not handle `$this->user()` returning null -- use null-safe `$this->user()?->can(...) ?? false`
- Missing `relationLoaded()` check in Resource -- causes N+1 query

See [the reference guide](references/module-template.md) for concrete file examples from the IAM module.
