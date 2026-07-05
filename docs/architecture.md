# Architecture

## Data Flow

```
Request -> Middleware -> Controller (__invoke) -> Action -> Eloquent -> Response
```

## Layers

### Controllers
`final readonly` invokable classes in `Modules/{Module}/Controllers/V1/`. They handle HTTP concerns only: parse request, call action, return response. No business logic.

### Actions
`final readonly` classes in `Modules/{Module}/Actions/`. Each action encapsulates a single business operation with a `handle()` method. Injectable via constructor.

### Models
Eloquent models in `Modules/{Module}/Models/`. Uses `HasDefaultBehavior` trait which applies ULID primary keys, soft deletes, and consistent `Y-m-d H:i:s` date serialization.

## Folder Structure

```
modules/
├── {Module}/
│   ├── Actions/         # Single-purpose use cases
│   ├── Controllers/     # V1/, V2/ for API versioning
│   ├── Database/
│   │   ├── factories/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── Events/
│   ├── Filters/         # Query/filter objects
│   ├── Jobs/
│   ├── Models/
│   ├── Payloads/        # DTOs with PHP 8.4 property hooks
│   ├── Providers/       # Service providers
│   ├── Repositories/    # Read-only data access (optional)
│   ├── Requests/        # Form request validation
│   ├── Resources/       # API resources
│   ├── Routes/          # V1.php, V2.php
│   └── Tests/           # Feature and unit tests
└── ...
app/                     # Shared application code
├── Concerns/            # Traits and shared logic
├── Contracts/           # Interfaces for DI
├── Http/
│   ├── Controllers/     # Base controller
│   ├── Middleware/      # ForceJsonResponse, etc.
│   └── Responses/       # SuccessResponse, ProblemResponse
├── Providers/           # AppServiceProvider
├── Models/              # Shared Eloquent models
├── Notifications/       # Shared notifications
├── Supports/            # Shared helpers and utilities
└── ...
```

### Current Modules

```
modules/
  IAM/    -- Identity and Access Management (Auth, User, Role, Permission)
```

## Response Types

- **Single resource**: `new SuccessResponse(...)`
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

## Service Providers

- **AppServiceProvider**: Rate limiters, `Password::defaults()`, `Gate::before()` for super-admin
- **RouteServiceProvider**: Loads module routes dynamically from `modules/*/Routes/{version}.php`
- **Module providers**: Each module has `{Module}ServiceProvider`
