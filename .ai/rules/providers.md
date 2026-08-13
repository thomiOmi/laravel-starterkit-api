---
paths:
  - 'modules/*/Providers/**'
  - 'app/Providers/ModuleServiceProvider.php'
  - 'app/Providers/ModuleLoaderServiceProvider.php'
---

# Providers

## Goal

`modules/{Module}/Providers/{Module}ServiceProvider.php` wires the module into the framework. Every module provider extends the abstract base `ModuleServiceProvider` (`app/Providers/`); the orchestrator `ModuleLoaderServiceProvider` (app) loads providers of ACTIVE modules from the central registry `config/modules.php`.

## Rules

1. The base class provides loading boilerplate: merges `Config/{alias}.php`, merges `features` from the registry, loads migrations, loads routes `Routes/V1.php`, loads translations `Lang/`, registers commands `Console/Commands` (no `withCommands` in `bootstrap/app.php`; module commands are registered by the base provider)
2. Module providers are declaration-only: `moduleName()` (abstract) and the `bootModule()` hook for middleware aliases, Pennant features, bindings (policies via `#[UsePolicy]` on models)
3. `register()`/`boot()` on the base are `final`; the loading order cannot be reordered by subclasses
4. Module activation only through the central registry (allow-list); an unregistered module = its provider is never booted. The base also guards `isModuleActive()` = `config()->boolean("modules.modules.{alias}.active", false)`, so a non-active module's provider stays inert even if registered manually. `mergeModuleFeatures()` publishes the registry `features` into `config("{alias}.features")`.
5. No hidden registration; middleware aliases are explicit, not magic discovery
6. The module alias is derived from `moduleName()` via `Str::snake()` (`'Media'` to `'media'`); the alias is used for config keys (`config('media.*')`), the `Config/{alias}.php` merge, and the route prefix (`api/v1/{module}`, see routes rule)
7. `OrganizationServiceProvider` is the exception: it extends stancl `TenancyServiceProvider` (not the base) because tenancy is opt-in via its own provider lifecycle

## Forbidden

- No module provider extending `ServiceProvider` directly (must extend base `ModuleServiceProvider`)
- No provider registering routes outside `Routes/`
- No `env()` in providers
