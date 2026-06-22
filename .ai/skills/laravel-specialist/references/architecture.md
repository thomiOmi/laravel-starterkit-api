# Architecture Reference

## Data Flow

```
Request
  -> Middleware (ForceJson, Auth, Throttle, TraceId, Locale, Can)
    -> Controller (final readonly __invoke)
      -> Action (final readonly, single responsibility)
        -> Repository (read-only: findById, paginate)
        OR
        -> Eloquent directly (writes: create, update, delete)
      <- Result (model, collection, or void)
    <- Response (JsonResponse or Resource)
  <- Client
```

## Module Structure

```
modules/{Module}/
  Actions/           -- Business logic layer
  Controllers/V1/    -- HTTP layer (invokable)
  Database/
    Factories/       -- Factory for model
    Migrations/      -- Table migrations
    Seeders/         -- Data seeders
  Filters/           -- Query filter classes
  Models/            -- Eloquent models
  Payloads/V1/       -- DTOs (typed data transfer)
  Providers/         -- Service provider (auto-registered)
  Repositories/      -- Read-only data access
  Requests/V1/       -- Form request validation
  Resources/         -- API resource transformers
  Routes/            -- Route files (V1.php)
  Tests/             -- Pest tests
```

## Key Patterns

### Single-Action Controller
```php
final readonly class ShowController
{
    public function __construct(
        private ShowUserAction $action,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $result = $this->action->handle($id);
        // ... return response
    }
}
```

### Action
```php
final readonly class SomeAction
{
    public function __construct(
        private SomeRepository $repository,
    ) {}

    public function handle(SomePayload $payload): Model
    {
        // business logic
    }
}
```

### Read-Only Repository
```php
final readonly class UserRepository
{
    public function __construct(
        private User $model,
    ) {}

    public function findById(string $id): ?User
    {
        return Cache::remember("user.{$id}", 300, fn () =>
            $this->model->with(['roles', 'permissions'])->find($id)
        );
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->model->query()
            ->when(isset($filters['search']), fn ($q) => ...)
            ->paginate();
    }
}
```

## Exception Handling

All handled in `bootstrap/app.php` renderer:
- `ValidationException` -> 422
- `AuthenticationException` -> 401
- `AccessDeniedHttpException` -> 403
- `NotFoundHttpException` -> 404
- `InvalidSignatureException` -> 403
- `TooManyRequestsHttpException` -> 429
- `InvalidArgumentException` -> 400

The `prepareException()` method converts `AuthorizationException` to `AccessDeniedHttpException` and `ModelNotFoundException` to `NotFoundHttpException` before render callbacks fire.

## Service Providers

- `AppServiceProvider`: Rate limiters, Password defaults, Gate::before (super-admin), email verification, password reset URL
- `RouteServiceProvider`: Loads module routes dynamically from `modules/*/Routes/*.php` via version iteration
- Module providers: Each module has `{Module}ServiceProvider` (migrations auto-loaded by `ModuleServiceProvider`)
