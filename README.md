# Laravel Starterkit API

Opinionated Laravel 13 starter kit for building scalable APIs. Modular architecture with single-action controllers, action classes, and strict typing.

[![tests](https://img.shields.io/github/actions/workflow/status/thomiOmi/laravel-starterkit-api/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/thomiOmi/laravel-starterkit-api/actions/workflows/tests.yml)
[![PHP](https://img.shields.io/badge/PHP-8.4-8892BF?style=flat-square)](composer.json)
[![license](https://img.shields.io/github/license/thomiOmi/laravel-starterkit-api?style=flat-square)](LICENSE)

## Features

- Modular architecture: self-contained modules under `modules/{Module}/` with their own routes, controllers, actions, payloads, models, and tests
- API versioning (`V1/`, `V2/`), RFC 9457 problem responses, uniform `SuccessResponse` / `ProblemResponse` envelopes
- Sanctum bearer-token auth, email verification, password reset, social auth (Google, GitHub)
- Role-based access control via Spatie `laravel-permission`
- Feature flags via Laravel Pennant
- ULID primary keys, idempotency middleware, sunset/deprecation headers, trace IDs
- Strict typing, single-action controllers, action classes, full quality gate via `composer ci:check`

## Technical Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 13 + PHP 8.4 |
| Auth | Sanctum (Bearer tokens) |
| RBAC | Spatie laravel-permission |
| Social Auth | Laravel Socialite (Google, GitHub) |
| Feature Flags | Laravel Pennant |
| Testing | Pest 5 |
| API Docs | Contract docs (`docs/api-standard.md`); no generated OpenAPI (ADR-0019) |
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

## Modules

| Module | Description | Docs |
|--------|-------------|------|
| IAM | Authentication, RBAC, social auth, feature flags | [auth.md](docs/auth.md), [rbac.md](docs/rbac.md) |
| Media | File uploads and media storage | [ADR-0015](docs/adr/0015-media-storage-module.md) |
| Organization | Multi-tenancy (stancl/tenancy, single database), disabled by default | - |

Modules are loaded from the allow-list in `config/modules.php`, following the Laravel Fortify pattern: a module is only active once it is listed there. Directories under `modules/` that are not listed are silently ignored (service provider, migrations, and routes are skipped), which also enables private modules -- keep the directory on disk and in `.gitignore` without registering it for customers. Create new modules with `php artisan make:module` (see [module-generator.md](docs/module-generator.md)).

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
│   ├── Builders/         # Query string filtering (extends BaseBuilder)
│   ├── Models/
│   ├── Payloads/        # Immutable DTOs with constructor promotion
│   ├── Providers/       # Service providers
│   ├── Requests/        # Form request validation
│   ├── Resources/       # API resources
│   ├── Routes/          # V1.php, V2.php
│   ├── Tests/           # Feature and unit tests
│   └── ...              # Other module-specific directories (e.g., Notifications, Jobs, etc.)
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
└── Unit/
```

## Documentation

| Document | Purpose |
|----------|---------|
| [docs/](docs/README.md) | Document map and index |
| [docs/prd/](docs/prd/README.md) | Product requirements (PRD, v1 scope) |
| [docs/adr/](docs/adr/README.md) | Architecture Decision Records (22 decisions, Nygard format) |
| [docs/api-standard.md](docs/api-standard.md) | API response contract and envelope shapes |
| [docs/architecture.md](docs/architecture.md) | Module structure and architecture patterns |
| [docs/auth.md](docs/auth.md) | Authentication flows (Sanctum, email verification, password reset) |
| [docs/coding-standards.md](docs/coding-standards.md) | Code style and language conventions |
| [docs/module-generator.md](docs/module-generator.md) | `make:module` usage and module scaffolding |
| [docs/rate-limiting.md](docs/rate-limiting.md) | Throttle configuration on auth routes |
| [docs/rbac.md](docs/rbac.md) | Roles, permissions, policies (Spatie) |
| [docs/testing.md](docs/testing.md) | Testing conventions: helpers, datasets, describe/it/group, TIA, probes |

## Testing

```bash
# Run all dev processes concurrently (server, queue, logs)
composer dev

# Full suite (unit + feature tests)
composer test

# Quality gate (lint + rector + static analysis + tests + profanity)
composer ci:check

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

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development setup, commit conventions, and quality gates. Security issues: see [SECURITY.md](SECURITY.md) and report privately via GitHub Private Vulnerability Reporting.

## License

The Laravel Starterkit API is open-sourced under the [MIT license](LICENSE). The Laravel framework itself is open-sourced under the MIT license.
