# ADR-0029: Standardize on nwidart/laravel-modules

- Status: Accepted
- Date: 2026-08-17

## Context

The kit originally shipped a hand-rolled module system: a custom `MakeModuleCommand` (1,422 lines) with `module:make`, an app-level `ModuleServiceProvider` base class, an orchestrator `ModuleLoaderServiceProvider` reading a central allow-list registry in `config/modules.php`, module routes in TitleCase `Routes/` directories loaded by the base provider, and custom stubs under `resources/stubs/`. This duplicated functionality that the mature, battle-tested `nwidart/laravel-modules` package provides (module scaffolding, config merging, migration loading, per-module manifests), and the central registry made activation a code-review ceremony instead of an operational status.

## Decision

- Adopt `nwidart/laravel-modules` as the module infrastructure. Remove the custom `MakeModuleCommand`, `ModuleListCommand`, `ModuleServiceProvider`, `ModuleLoaderServiceProvider`, `resources/stubs/`, and the central allow-list registry in `config/modules.php`.
- Modules are generated with `php artisan module:make {Name}` from `stubs/module-generator/` (kit-owned overrides of the vendor stubs) and carry `module.json` + `composer.json`.
- Module activation is nwidart FileActivator: live status in `modules_statuses.json` (e.g. `{"iam": true}`); a module only boots when listed as enabled. `module:enable` / `module:disable` / `module:delete` manage status.
- Module structure standardizes on lowercase root dirs mirroring Laravel: `app/` (mirrors `app/`, including `Http/Controllers`, `Http/Requests`, `Http/Resources`), `config/`, `database/` (factories, migrations, seeders), `routes/`, `tests/`. This deliberately deviates from nwidart TitleCase defaults (`Database/`, `Routes/`, `Tests/`).
- Module providers extend `Nwidart\Modules\Support\ModuleServiceProvider`, which auto-merges `config/config.php`, loads `database/migrations` + `database/factories`, and registers the module's own providers from a `$providers` array.
- Routes are loaded by each module's own `app/Providers/RouteServiceProvider.php` (extends `Illuminate\Foundation\Support\Providers\RouteServiceProvider`), iterating `apiroute.supported_versions` (default `['V1']`), guarding each file with `file_exists`, mounting on `api/{version}` with name prefix `{version}.{alias}.` (supersedes ADR-0008's single central discovery).
- The uniform route contract is unchanged: final URLs `api/v1/{path}` (no module segment), final names `v1.{module}.{name}` (e.g. `v1.iam.users.index`, `v1.blog.posts.index`).
- Scaffold route files (`routes/api.php`) keep `declare(strict_types=1)`, `auth:sanctum` middleware, `apiResource`, and the `v1.` name prefix produced by the generated RouteServiceProvider.

## Consequences

- Module scaffolding is no longer kit-maintained code; updates track the vendor package while kit stubs pin the output shape (backend-only modules that pass the architecture tests).
- Activation is operational (`modules_statuses.json`), not a config registry; `module:enable` is the opt-in switch.
- Enabling or disabling a module is a status-file change instead of a code-review change in `config/modules.php` (reverse of ADR-0024, which is superseded).
- Per-module `composer.json` + `module.json` reintroduce package overhead the kit previously avoided; autoloading relies on nwidart's module discovery and `ModuleServiceProvider` registration.
- Existing docs, rules, and skills describing the custom system must reference the nwidart mechanics: `module:make`, per-module `RouteServiceProvider`, lowercase module root dirs.
- Migration verified by the full suite: architecture tests (73) plus `ModuleMakeCommandTest` assert scaffolded modules boot, carry strict types, and match the route contract.