---
name: modular-architecture
description: >
  Scaffold and extend Laravel modules following the project's DDD-style
  structure with Actions, Controllers, Filters, Payloads, Requests, Resources,
  Routes, Services, and Database layers. Use when creating a new module
  ("buat module baru"), adding a feature ("add feature", "bikin controller",
  "create action", "tambah filter"), or following the project's modular
  conventions ("module pattern", "modular structure").
metadata:
  version: "1.0"
---

# Modular Architecture

## Module Directory Structure

Each module lives under `modules/{ModuleName}/` and follows this layout:

```
modules/{ModuleName}/
  Actions/             -- Single-responsibility business actions
  Controllers/
    V1/                -- Invokable single-action controllers (API v1)
  Database/
    Factories/         -- Model factories
    Migrations/        -- Database migrations
    Seeders/           -- Database seeders
  Filters/             -- Query builder filters extending BaseFilter
  Models/              -- Eloquent models
  Payloads/
    V1/                -- Immutable DTOs for action input (API v1)
  Providers/           -- Module service provider
  Requests/
    V1/                -- FormRequest validation (API v1)
  Resources/           -- Eloquent API resources
  Routes/              -- Route definitions
  Services/            -- Business services (optional)
  Tests/
    Feature/           -- Pest feature tests
    Unit/              -- Pest unit tests
```

## Auto-Discovery

Module registration is automatic. `App\Providers\ModuleServiceProvider` scans all directories under `modules/` at boot time. For each directory it:

1. Looks for `Modules\{Name}\Providers\{Name}ServiceProvider` and registers it
2. Loads migrations from `modules/{Name}/Database/Migrations/`

You do NOT need to register modules in `config/app.php`.

The module's own `{ModuleName}ServiceProvider` is responsible for registering routes.

## Step: Create a New Module

1. Create `modules/{ModuleName}/` with all subdirectories listed above
2. Create `Providers/{ModuleName}ServiceProvider.php` extending `ServiceProvider`
3. In the provider's `boot()` method, register routes via `Route::prefix('api/v1')...`
4. Module is auto-discovered on next request (no manual registration needed)
5. Run `php artisan make:migration --path=modules/{ModuleName}/Database/Migrations` for new tables

## Step: Add a New Feature

Follow this order when adding a new CRUD feature (e.g., a new resource):

1. **Model** -- Define the Eloquent model with `#[Fillable]`, `#[Hidden]`, `#[UseFactory]` attributes
2. **Migration** -- Create the table migration
3. **Factory** -- Create the model factory
4. **Route** -- Add routes to `Routes/V1.php`
5. **Controller** -- Create invokable controller
6. **Action** -- Create single-responsibility action
7. **Payload** -- Create immutable DTO (for create/update)
8. **Request** -- Create FormRequest with validation + authorization
9. **Filter** -- Create filter extending `BaseFilter` (for list actions)
10. **Resource** -- Create API resource
11. **ServiceProvider** -- Register routes in the module provider
12. **Unit Test** -- Test the Action in isolation
13. **Feature Test** -- Test the full HTTP flow

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

- Namespace: `Modules\{Module}\Controllers\V1`
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
    #[\NoDiscard]
    public function handle({Payload} $payload): {Model}
    {
        // ...
    }
}
```

- Namespace: `Modules\{Module}\Actions`
- Must be `final readonly`
- Must have `handle()` method annotated with `#[\NoDiscard]` (PHP 8.4) to warn if return value is discarded
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

### Filter

```php
/** @extends BaseFilter<{Model}> */
class {Resource}Filter extends BaseFilter
{
    protected array $allowedFilters = ['name', 'status'];
    protected array $allowedSorts = ['name', 'created_at'];
    protected array $allowedFields = ['id', 'name', 'created_at', 'updated_at'];

    public function search(Builder $builder, string $value): Builder
    {
        return $this->applySearch($builder, $value, ['name']);
    }

    public function status(Builder $builder, mixed $value): Builder
    {
        // custom filter logic
    }
}
```

- Namespace: `Modules\{Module}\Filters`
- Must extend `BaseFilter`
- Must whitelist `$allowedFilters`, `$allowedSorts`, `$allowedFields`
- Filter methods are dispatched by `BaseFilter` from `?filter[key]=value` query params

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

- Namespace: `Modules\{Module}\Requests\V1`
- Must have `payload()` method returning a Payload
- Must implement `authorize()` with Spatie `can()` checks
- Use `PasswordValidationRules` and `ProfileValidationRules` concerns when needed
- POST vs PUT/PATCH detection via `$this->isMethod('POST')`

### Resource

```php
class {Resource}Resource extends JsonResource
{
    use FormatDates;

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

- Namespace: `Modules\{Module}\Resources`
- Must extend `JsonResource`
- Must use `App\Concerns\FormatDates` trait
- Must guard eager-loaded relations with `relationLoaded()`
- All datetime fields must use `Y-m-d H:i:s` format

### Route

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

- Route names: `v1.{module}.{name}` (prefix set in ServiceProvider)
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
class {Module}ServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRoutes();
    }

    public function register(): void {}

    protected function configureRoutes(): void
    {
        Route::prefix('api/v1')
            ->middleware('api')
            ->name('v1.')
            ->group(base_path('modules/{Module}/Routes/V1.php'));
    }
}
```

### Tests

Unit test per Action (test business logic in isolation). Feature test per endpoint (test the full HTTP request/response cycle). Use custom expectations: `toBeSuccessResponse(status)`, `toBeProblemResponse(status)`, `toBePaginated()`.

## Project-Specific Rules (Must Do)

- Return `SuccessResponse` or `ProblemResponse` from controllers (never `JsonResponse`)
- Use `payload()` method on Request to get the Payload DTO
- Extend `BaseFilter` for all query filtering
- Use `App\Concerns\FormatDates` trait on all Resources
- Use `App\Concerns\HasDefaultBehavior` trait on all Models
- Use PHP 8 attributes (`#[Fillable]`, `#[Hidden]`, `#[UseFactory]`) on Models, not class properties
- Use `config()->string()` / `config()->integer()` / `config()->boolean()` / `config()->array()` for config access
- Use `relationLoaded()` in Resources to guard against N+1
- Write Unit test per Action + Feature test per endpoint
- Use `(string)` / `(int)` / `(bool)` for type casting over function calls
- Route naming format: `v1.{module}.{name}`
- Add `#[\NoDiscard]` on Action `handle()` methods (PHP 8.4) to prevent accidentally discarding return values
- Use `#[Middleware]` attribute (Laravel 13+) on controllers as an alternative to defining middleware in route groups

## Project-Specific Prohibitions (Must Do Not)

- Do not import Models in Payloads or Requests
- Do not use `Illuminate\Http\Request` in Actions
- Do not use `Config` facade in business code
- Do not use `@phpstan-ignore` comments -- fix the root cause
- Do not register modules manually in `config/app.php` -- auto-discovery handles it
- Do not use `$fillable`, `$hidden`, `$table` class properties -- use PHP attributes instead
- Do not use PHP 8.4 property hooks on Eloquent model properties -- Eloquent's magic accessor system bypasses native property hooks; use accessors/mutators instead

## Common Pitfalls

- Forgetting `declare(strict_types=1)` at the top of every PHP file
- Forgetting `#[UseFactory(ModelFactory::class)]` on Model
- Route not registered in `{Module}ServiceProvider::boot()` -- module works but routes return 404
- `toArray()` in Payload does not filter null values -- causes SQL errors on update when password is not provided
- `authorize()` does not handle `$this->user()` returning null -- use null-safe `$this->user()?->can(...) ?? false`
- Missing `relationLoaded()` check in Resource -- causes N+1 query

See [the reference guide](references/module-template.md) for concrete file examples from the IAM module.
