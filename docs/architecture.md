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
  Actions/           -- Business logic (Create, Update, Delete actions)
  Controllers/V1/    -- HTTP layer (final readonly __invoke)
  Database/
    Factories/       -- Model factories
    Migrations/      -- Table migrations
    Seeders/         -- Data seeders
  Events/            -- Domain events (optional)
  Filters/           -- Query string filtering (search, sort, status)
  Models/            -- Eloquent models with HasDefaultBehavior
  Payloads/V1/       -- Typed DTOs for action input
  Providers/         -- Service provider (auto-registered by ModuleServiceProvider)
  Repositories/      -- Read-only data access (findById, paginate)
  Requests/V1/       -- Form request validation
  Resources/         -- API resource transformers
  Routes/            -- Route files (V1.php loaded by RouteServiceProvider)
  Tests/             -- Pest tests (Feature + Unit)
```

### Current Modules

```
modules/
+---Auth              -- Authentication, devices, social login
+---Role              -- Roles & permissions CRUD, Spatie integration
\---User              -- User CRUD, bulk actions, UserCreated event
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
