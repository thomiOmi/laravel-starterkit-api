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
│   ├── Middleware/      # Sunset, TraceId, SetLocale, PlanFeature, SecurityHeaders
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
├── Feature/             # Infrastructure tests (middleware, responses, etc.)
│   └── Infrastructure/
├── Unit/
└── Integration/
```

## Testing

```bash
# Full suite (lint + static analysis + tests)
composer test

# Quality gate (lint + static analysis + tests + coverage)
composer test:quality

# Mutation testing
composer test:mutation

# Profanity check
composer test:profanity
```

## Code Quality

```bash
# Auto-fix staged files (pre-commit)
composer lint:staged

# Auto-fix all files
composer lint

# Static analysis
composer types:check

# Full CI pipeline
composer ci:check
```

## Project Setup

```bash
# Install dependencies and prepare the application
composer setup

# Prepare for CI (no composer install, uses SQLite)
composer setup:ci
```

## License

The Laravel framework is open-sourced under the MIT license.
