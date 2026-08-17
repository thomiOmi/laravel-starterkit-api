---
paths:
  - 'modules/**'
  - 'stubs/module-generator/**'
---

# Module Structure

## Goal

Modules mirror the stock Laravel `app/` skeleton (mirror principle): `modules/{Module}/app/` mirrors `app/`, with the container folders `Http/` (Controllers, Middleware, Requests, Resources) housing HTTP layers exactly like `app/Http`. Module-scoped concerns live inside the module; shared concerns live in `app/` (shared vocabulary). Modules are generated with `php artisan module:make {Name}` (nwidart) and carry per-module metadata (`module.json`, `composer.json`) plus lowercase `config/`, `database/`, `routes/`, `tests/` directories (deviation from nwidart defaults, which use TitleCase `Database/` etc. - this kit standardizes on the Laravel lowercase convention).

## Rules

1. Module root layout: `app/` (mirrors Laravel app), `config/`, `database/` (factories, migrations, seeders), `routes/` (V1.php), `tests/` (Feature, Unit), `module.json`, `composer.json`
2. Module provider `app/Providers/{Module}ServiceProvider.php` extends `Nwidart\Modules\Support\ModuleServiceProvider` (see providers rule); `app/Providers/RouteServiceProvider.php` loads `routes/` files (see routes rule)
3. Required on ACTIVE modules: `app/Providers`, `routes`, `tests`; the rest are optional and created when needed
4. Optional folders (only created if they contain at least 1 file, empty folders forbidden): Http (Controllers, Middleware, Requests, Resources), Console (Commands), Exceptions, Features (Pennant class-based features), Jobs, Mail, Rules, Events, Listeners, Lang ({locale}/), Models, Observers, Policies, Scopes, Notifications, Actions, Builders, Services, Payloads, Support, Contracts, Enums, Config ({alias}.php), Database (Migrations, Factories, Seeders)
5. Kit-specific layers without a skeleton counterpart: Actions, Services, Payloads, Builders, Features, Config, Routes, Database, Tests, Lang
6. `app/Http/Responses` is a global contract and is not mirrored into modules
7. Module alias is the lowercase module name (`iam`), not the TitleCase folder name; used for config keys, `config/{Module}.php`, and the route name prefix (`api.v1.iam.`)

On-demand layers (Jobs, Events, Listeners, Mail, Rules, Exceptions, Lang, Observers, Scopes):

1. Created only if they contain at least 1 file (empty folders forbidden)
2. `Lang/` is loaded by the nwidart base `ModuleServiceProvider` while the module is active
3. Detailed rules just follow Laravel conventions; no separate rule file per folder
4. Module listeners are NOT auto-discovered (bootstrap only scans `app/Listeners`); register listeners explicitly in `boot()` via `Event::listen`/`Event::subscribe`

## Forbidden

- No empty folders as placeholders
- No hand-rolled module bootstrapping (no custom auto-discovery providers, no `config/modules.php` registry wiring) - nwidart handles activation via `modules_statuses.json` (FileActivator)

## Module Generator (`stubs/module-generator/`)

1. `php artisan module:make {Name}` scaffolds a backend-only module: `module.json`, `composer.json`, `config/config.php`, `routes/V1.php`, `app/Providers/{Module}ServiceProvider.php` + `EventServiceProvider.php` + `RouteServiceProvider.php`, `app/Http/Controllers/{Module}Controller.php`, `database/seeders/{Module}DatabaseSeeder.php`, `tests/`
2. Options: `--api` (generates API routes/controller), `--disabled` (module.json disabled), `--plain` (no scaffolding)
3. Stubs must stay in sync with the module conventions: final readonly resource controllers (no create), `declare(strict_types=1)` everywhere, `file_exists` guards in RouteServiceProvider, route name `api.{version}.{module}.` prefix (see console-commands rule)
4. New kit layers require a matching stub so the generator produces convention-compliant files