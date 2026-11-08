---
paths:
  - 'modules/*/app/Providers/**'
  - 'app/Providers/AppServiceProvider.php'
  - 'app/Providers/RouteServiceProvider.php'
---

# Providers

## Goal

`modules/{Module}/app/Providers/{Module}ServiceProvider.php` wires the module into the framework. Every module provider extends `Nwidart\Modules\Support\ModuleServiceProvider` (auto-discovered by nwidart via `module.json` + `modules_statuses.json`); the nwidart base merges `config/config.php`, loads `database/migrations` and `database/factories`, and registers the module's providers from the `$providers` array (e.g. `RouteServiceProvider`). `app/Providers` hosts framework wiring only (`AppServiceProvider` for env-driven defaults, `RouteServiceProvider` for web routes); module behavior never lives in app providers.

## Rules

1. The nwidart base class provides loading boilerplate: merges `config/config.php` into `config('{alias}.*')`, loads `database/migrations`, registers the module's providers (`EventServiceProvider`, `RouteServiceProvider`) from the `$providers` array
2. Module providers declare `$name`, `$nameLower`, `$providers`, and optional `$commands`; they may override `register()`/`boot()` for config merge, bindings, and middleware aliases (policies via `#[UsePolicy]` on models)
3. Module commands are listed in the `$commands` array; the base `registerCommands()` calls `$this->commands($this->commands)` during `boot()`, so `bootstrap/app.php` needs no `withCommands`
4. Module activation is managed by nwidart (FileActivator + `modules_statuses.json`); a disabled module's provider is never booted
5. No hidden registration; middleware aliases are explicit, not magic discovery
6. The module alias is the lowercase module name (nwidart convention); used for config keys (`config('iam.*')`), the `config/config.php` merge, and the route name prefix (see routes rule)

## Forbidden

- No module provider extending `Illuminate\Support\ServiceProvider` directly (must extend `Nwidart\Modules\Support\ModuleServiceProvider`)
- No provider registering routes outside the module's `routes/` directory (routes load via the module's own `RouteServiceProvider`)
- No `env()` in providers