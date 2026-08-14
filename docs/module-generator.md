# Module Generator

Creates a new module with standardized structure.

```bash
php artisan make:module {name?} [options]
```

## Options

| Flag | Shorthand | Description |
|------|-----------|-------------|
| `--force` | | Overwrite existing files |
| `--api-version=V1` | | API version |
| `--except=...` | `-x` | Skip components: repository,action,filter,migration,factory,seeder,event |
| `--event` | `-E` | Create event |
| `--repository` | `-r` | Create repository |
| `--action` | `-a` | Create CRUD actions and payloads |
| `--filter` | `-l` | Create query filter |
| `--migration` | `-m` | Create migration |
| `--factory` | `-y` | Create factory |
| `--seeder` | `-s` | Create seeder |

All shorthand flags: `-Eralmys`

## Structure

A created module has:

```
modules/{Module}/
  Actions/              -- Business logic (Create, Update, Delete, Bulk*)
  Controllers/V1/       -- Single-action invokable controllers
  Database/
    Factories/          -- Model factories
    Migrations/         -- Database migrations
    Seeders/            -- Database seeders
  Builders/             -- BaseQueryBuilder subclasses for list queries
  Models/               -- Eloquent models
  Payloads/V1/          -- DTOs for action input
  Providers/            -- Service provider
  Requests/V1/          -- Form request validation
  Resources/            -- API resource transformers
  Routes/               -- Route files (v1.php)
  Tests/                -- Feature and unit tests
```

Optional directories (created with `--event` or `--repository` flags):

```
  Events/               -- Event classes (--event / -E)
  Repositories/         -- Read-only data access (--repository / -r)
```

## Registration

Modules are auto-detected by `RouteServiceProvider` and `ModuleServiceProvider`. The service provider must exist at `modules/{Module}/Providers/{Module}ServiceProvider.php`.
