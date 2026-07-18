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
Eloquent models in `Modules/{Module}/Models/`. Uses `HasDefaultBehavior` trait which applies ULID primary keys, soft deletes, and consistent `Y-m-d H:i:s` date serialization. Configured via PHP 8 attributes (`#[Fillable]`, `#[Hidden]`, `#[UseFactory]`).

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
│   ├── Filters/         # Query string filtering (extends BaseFilter)
│   ├── Models/
│   ├── Payloads/        # Immutable DTOs with constructor promotion
│   ├── Providers/       # Service providers
│   ├── Requests/        # Form request validation
│   ├── Resources/       # API resources
│   ├── Routes/          # V1.php, V2.php
│   └── Tests/           # Feature and unit tests
└── ...
app/                     # Shared application code
├── Concerns/            # Traits (FormatDates, HasDefaultBehavior, etc.)
├── Contracts/           # Interfaces (Identity)
├── Http/
│   ├── Controllers/     # Base controller
│   ├── Middleware/      # ForceJsonResponse, Sunset, TraceId, SetLocale, PlanFeature, SecurityHeaders
│   └── Responses/       # SuccessResponse, ProblemResponse (RFC 9457)
├── Providers/           # AppServiceProvider, ModuleServiceProvider
└── Notifications/       # Shared notifications
```

### Current Modules

```
modules/
  IAM/    -- Identity and Access Management (Auth, User, Role, Permission)
```

## Response Types

- **Single resource**: `new SuccessResponse(data: new {Resource}Resource($model), ...)`
- **Paginated collection**: `new SuccessResponse(data: {Resource}Resource::collection($models), ...)`
- **Error**: `new ProblemResponse(...)` (RFC 9457)

## Exception Handling

Handled in `bootstrap/app.php`:
- 400: `InvalidArgumentException`
- 401: `AuthenticationException`
- 403: `AccessDeniedHttpException`, `InvalidSignatureException`
- 404: `NotFoundHttpException`
- 422: `ValidationException`
- 429: `TooManyRequestsHttpException`

## Service Providers

- **AppServiceProvider**: Rate limiters, `Password::defaults()`, `Gate::before()` for super-admin, feature flag definitions, email verification, password reset, production security monitoring
- **ModuleServiceProvider**: Auto-discovers and registers all module providers by scanning `modules/` directory
- **Module providers**: Each module has `{Module}ServiceProvider` responsible for registering its own routes
