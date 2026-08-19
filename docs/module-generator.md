# Module Generator

Creates a new module with the standard structure using `nwidart/laravel-modules` (see [ADR-0029](adr/0029-nwidart-laravel-modules.md)). The kit overrides the vendor stubs via `stubs/module-generator/` so generated modules are backend-only, strict-typed, and pass the architecture tests.

```bash
php artisan module:make {name} [options]
```

## Options

| Flag | Description |
|------|-------------|
| `--api` | Generate API scaffolding (routes + controller) |
| `--disabled` | Create the module with `module.json` marked disabled (not booted until `module:enable`) |
| `--plain` | Skip scaffolding (empty module skeleton) |

Related lifecycle commands: `php artisan module:enable {name}`, `php artisan module:disable {name}`, `php artisan module:delete {name} --force`, `php artisan module:list`.

## Structure

A created module has:

```
modules/{Module}/
  app/
    Http/Controllers/     -- Scaffold resource controller (final readonly, no create)
    Providers/            -- {Module}ServiceProvider, EventServiceProvider, RouteServiceProvider
  config/
    config.php            -- Merged into config('{alias}.*') by nwidart
  database/
    seeders/              -- {Module}DatabaseSeeder
  routes/
    V1.php                -- Scaffold routes (auth:sanctum, apiResource)
  tests/
  composer.json           -- Per-module package metadata (nwidart)
  module.json             -- nwidart module manifest
```

All generated PHP files carry `declare(strict_types=1)`. The generated `RouteServiceProvider` guards both route files with `file_exists`, iterates `apiroute.supported_versions`, and mounts api routes on `prefix('api/{version}')` with `name('api.{version}.{alias}.')`, producing the uniform contract: URL `api/v1/{path}`, route name `api.{version}.{module}.{name}`.

## Layer commands

Layer make commands write convention-compliant files into the module (paths + namespaces from `config/modules.php` `paths.generator`):

| Command | Path | Notes |
|---|---|---|
| `module:make-model {Name}` | `app/Models/` | Attribute-based stub (`#[Fillable]`, `#[Hidden]`, `#[UseFactory]`), non-final |
| `module:make-scope {Name}` | `app/Models/Scopes/` | nwidart derives the namespace from the model path |
| `module:make-action` / `--invoke` | `app/Actions/` | `final readonly` |
| `module:make-service` / `--invoke` | `app/Services/` | `final readonly` |
| `module:make-helper` / `--invoke` | `app/Support/` | Final static-utility class (helpers = Support layer) |
| `module:make-interface {Name}` | `app/Contracts/` | |
| `module:make-resource` / `--collection` | `app/Http/Resources/` | |
| `module:make-request {Name}` | `app/Http/Requests/` | |
| `module:make-middleware` | `app/Http/Middleware/` | |
| `module:make-command {Name}` | `app/Console/Commands/` | `#[Signature]`/`#[Description]`, `handle(): int` |
| `module:make-mail {Name}` | `app/Mail/` | |
| `module:make-factory` | `database/factories/` | |
| `module:make-migration` | `database/migrations/` | |
| `module:make-seeder` | `database/seeders/` | |

Layer commands follow Laravel conventions for the rest (policy, observer, enum, exception, job, event, listener, rule, notification). Repository (`module:make-repository`) stays on-demand; casts, channels, classes, traits, component-class, and replacement remain nwidart defaults (outside the anatomy).

## Activation

Modules are activated via the nwidart FileActivator status in `modules_statuses.json` (e.g. `{"IAM": true}`). A module only boots (config merge, migrations, routes, providers) when its entry is `true`. `module:enable {name}` is the opt-in switch; there is no environment override.

## API Modules

The scaffold already follows the IAM contract:

1. `routes/V1.php` is generated versioned by default; additional versions follow `V{number}.php` casing and are listed in `config/apiroute.php` `supported_versions` (default `['V1']`)
2. The module's `app/Providers/RouteServiceProvider.php` mounts each existing `routes/V{version}.php` on `api/{version}` with name prefix `api.{version}.{alias}.`

See [architecture.md](architecture.md) for the full module anatomy.
