# Architecture

## Data Flow

```
Request -> Middleware -> Controller (__invoke) -> Action -> Repository (read) / Eloquent (write) -> Response
```

## Layers

### Controllers
`final readonly` invokable classes in `Modules/{Module}/Controllers/V1/`. They handle HTTP concerns only: parse request, call action, return response. No business logic.

### Actions
`final readonly` classes in `Modules/{Module}/Actions/`. Each action encapsulates a single business operation with a `handle()` method. Injectable via constructor.

### Repositories
Read-only data access in `Modules/{Module}/Repositories/`. Provides `findById()` and `paginate()` methods with optional caching. Writes (create, update, delete) use Eloquent models directly inside actions.

### Models
Eloquent models in `Modules/{Module}/Models/`. Uses `HasDefaultBehavior` trait which applies ULID primary keys, soft deletes, and consistent `Y-m-d H:i:s` date serialization.

## Module Structure

```
modules/{Module}/
  Actions/           -- Business logic
  Controllers/V1/    -- HTTP layer
  Database/
    Factories/       -- Model factories
    Migrations/      -- Table migrations
    Seeders/         -- Data seeders
  Filters/           -- Query string filtering
  Models/            -- Eloquent models
  Payloads/V1/       -- Typed DTOs
  Providers/         -- Service provider (auto-registered)
  Repositories/      -- Read-only data access
  Requests/V1/       -- Form request validation
  Resources/         -- API resource transformers
  Routes/            -- Route files (V1.php)
  Tests/             -- Pest tests
```

## Response Types

- **Single resource**: `new JsonResponse([...], status)`
- **Paginated collection**: `ResourceCollection::additional([...])->response()`
- **Error**: `ProblemResponse` (RFC 9457)

## Exception Handling

Handled in `bootstrap/app.php`:
- 400: `InvalidArgumentException`
- 401: `AuthenticationException`
- 403: `AccessDeniedHttpException`, `InvalidSignatureException`
- 404: `NotFoundHttpException`
- 422: `ValidationException`
- 429: `TooManyRequestsHttpException`

Laravel's `prepareException()` converts `AuthorizationException` to `AccessDeniedHttpException` and `ModelNotFoundException` to `NotFoundHttpException` before render callbacks.

## Service Providers

- **AppServiceProvider**: Rate limiters, `Password::defaults()`, `Gate::before()` for super-admin, email verification config, password reset URL config
- **RouteServiceProvider**: Loads module routes dynamically from `modules/*/Routes/{version}.php`
- **Module providers**: Each module has `{Module}ServiceProvider`; migrations are loaded by `ModuleServiceProvider`
