# Laravel Starterkit API

Opinionated Laravel 13 starter kit for building scalable APIs. Modular architecture with single-action controllers, action classes, and strict typing.

## Technical Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 13 + PHP 8.4 |
| Auth | Sanctum (Bearer tokens) |
| RBAC | Spatie laravel-permission |
| Social Auth | Laravel Socialite (Google, GitHub) |
| Feature Flags | Laravel Pennant |
| Testing | Pest 4 |
| API Docs | - |
| Static Analysis | PHPStan level max |
| Code Style | Laravel Pint |

## Quick Start

```bash
composer run setup
```

Or manually:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Architecture

```
Request -> Middleware -> Controller (__invoke) -> Action -> Eloquent -> Response
```

- **Controllers** are `final readonly` invokable classes -- no business logic.
- **Actions** encapsulate single business operations with a `handle()` method.
- **Modules** are self-contained in `modules/{Module}/` with their own routes, controllers, actions, payloads, models, and tests.

## Structure

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
│   ├── Middleware/      # ForceJsonResponse, Sunset, TraceId, etc.
│   └── Responses/       # SuccessResponse, ProblemResponse (RFC 9457)
├── Providers/           # AppServiceProvider, ModuleServiceProvider
└── Notifications/       # Shared notifications (VerifyEmail, ResetPassword)
config/
database/
├── factories/           # Shared factories
├── migrations/          # Shared migrations
└── seeders/             # Shared seeders
routes/
├── api.php              # (reserved for future use -- modules auto-register via ModuleServiceProvider)
└── console.php
tests/                   # Shared tests / global test helpers
├── Architecture/        # Architecture tests (N+1, module isolation, etc.)
├── Feature/
└── Unit/
```

## Testing

```bash
# Full suite
php artisan test --compact

# Parallel
php artisan test --compact --parallel

# Single module
php artisan test --compact --filter=UserManagementTest

# Type coverage
php -d memory_limit=2G artisan test --type-coverage
```

## Code Quality

```bash
./vendor/bin/pint --dirty --format agent
./vendor/bin/phpstan analyse --memory-limit=2G
```

## License

The Laravel framework is open-sourced under the MIT license.
