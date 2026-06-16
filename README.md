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
| API Docs | Scramble (OpenAPI) |
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

Swagger UI: `/docs/api`

## Architecture

```
Request -> Middleware -> Controller (__invoke) -> Action -> Repository (read) / Eloquent (write)
```

- **Controllers** are `final readonly` invokable classes -- no business logic.
- **Actions** encapsulate single business operations.
- **Repositories** are read-only (query/find). Writes use Eloquent directly in actions.
- **Modules** are self-contained in `modules/{Module}/` with their own routes, controllers, actions, models, and tests.

## Modules

```
modules/
+---Auth
|   +---Actions/          -- Login, Register, SocialCallback, etc.
|   +---Controllers/V1/   -- 13 invokable controllers
|   +---Payloads/V1/      -- LoginPayload, RegisterPayload
|   +---Providers/
|   +---Requests/V1/      -- Form requests with validation
|   +---Resources/
|   +---Routes/            -- api/v1/auth/*
|   \---Tests/             -- Feature + Unit (11 files)
+---Role
|   +---Actions/           -- CRUD + Bulk for roles & permissions
|   +---Controllers/V1/    -- 10 invokable controllers
|   +---Database/
|   |   +---Factories/
|   |   +---Migrations/
|   |   \---Seeders/
|   +---Filters/
|   +---Models/            -- Role, Permission (Spatie)
|   +---Payloads/V1/
|   +---Providers/
|   +---Repositories/
|   +---Requests/V1/
|   +---Resources/
|   +---Routes/            -- api/v1/roles/*, api/v1/permissions/*
|   \---Tests/             -- Feature + Unit (4 files)
\---User
    +---Actions/           -- CRUD + Bulk for users
    +---Controllers/V1/    -- 6 invokable controllers
    +---Database/
    |   +---Factories/     -- UserFactory
    |   \---Seeders/
    +---Events/            -- UserCreated
    +---Filters/
    +---Models/            -- User (MustVerifyEmail, HasRoles)
    +---Payloads/V1/
    +---Providers/
    +---Repositories/
    +---Requests/V1/
    +---Resources/
    +---Routes/            -- api/v1/users/*
    \---Tests/             -- Feature + Unit (3 files)
```

## Testing

```bash
# Full suite (157+ tests)
php artisan test --compact

# Single module
./vendor/bin/pest modules/User

# Filter by test name
php artisan test --compact --filter=SocialLoginTest
```

## Code Quality

```bash
./vendor/bin/pint --format agent
./vendor/bin/phpstan analyse --memory-limit=512M
```

## License

The Laravel framework is open-sourced under the MIT license.
