# Laravel Starterkit API

Opinionated Laravel 13 starter kit for building scalable APIs. Modular architecture with single-action controllers, action classes, read-only repositories, and strict typing.

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
Request -> Middleware -> Controller (__invoke) -> Action -> Repository (read) / Eloquent (write)
```

- **Controllers** are `final readonly` invokable classes -- no business logic.
- **Actions** encapsulate single business operations.
- **Repositories** are read-only (query/find). Writes use Eloquent directly in actions.
- **Modules** are self-contained in `modules/{Module}/` with their own routes, controllers, actions, models, and tests.

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
│   ├── Events/
│   ├── Filters/         # Query/filter objects
│   ├── Jobs/
│   ├── Models/
│   ├── Payloads/        # DTOs with PHP 8.4 property hooks
│   ├── Providers/       # Service providers
│   ├── Repositories/
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
config/
database/
├── factories/           # Shared factories
├── migrations/          # Shared migrations
└── seeders/             # Shared seeders
routes/
├── api.php              # Module route loader
└── console.php
tests/                   # Shared tests / global test helpers
├── Architecture/        # Architecture tests (e.g., modular structure)
├── Feature/
└── Unit/
```

## Testing

```bash
# Full suite
php artisan test --compact

# Single module
./vendor/bin/pest modules/User

# Filter by test name
php artisan test --compact --filter=SocialLoginTest
```

## Code Quality

```bash
./vendor/bin/pint
./vendor/bin/phpstan analyse --memory-limit=512M
```

## License

The Laravel framework is open-sourced under the MIT license.
