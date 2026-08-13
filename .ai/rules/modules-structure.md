---
paths:
  - 'modules/**'
---

# Module Structure

## Goal

Modules mirror the stock Laravel `app/` skeleton (mirror principle): `modules/{Module}/` mirrors `app/`, with the container folders `Http/` (Controllers, Middleware, Requests, Resources) and `Console/` (Commands) housing HTTP/CLI layers exactly like `app/Http` and `app/Console`. Module-scoped concerns live inside the module; shared concerns live in `app/` (shared vocabulary). No per-module overhead: no `composer.json`, `module.json`, `resources/assets`, or `vite.config.js` (deviation from nWidart/laravel-modules).

## Rules

1. Only 3 folders are required on ACTIVE modules: `Providers`, `Routes`, `Tests`; the rest are optional and created when needed
2. Required folders: `Providers` ({Module}ServiceProvider extends base ModuleServiceProvider), `Routes` (V1.php), `Tests` (feature and unit tests)
3. Optional folders (only created if they contain at least 1 file, empty folders forbidden): Http (Controllers, Middleware, Requests, Resources), Console (Commands), Exceptions, Features (Pennant class-based features), Jobs, Mail, Rules, Events, Listeners, Lang ({locale}/), Models, Observers, Policies, Scopes, Notifications, Actions, Builders, Services, Payloads, Support, Contracts, Enums, Config ({alias}.php), Database (Migrations, Factories, Seeders)
4. Kit-specific layers without a skeleton counterpart: Actions, Services, Payloads, Builders, Features, Config, Routes, Database, Tests, Lang
5. `app/Http/Responses` is a global contract and is not mirrored into modules
6. Inactive modules (not registered as active in the central registry) minimally contain `Providers`, `Tests` (example: Organization); the rest of the structure appears when the module is activated
7. Module alias is the lowercase registry key (`media`), not the TitleCase folder name; used for config keys, `Config/{alias}.php`, and the route prefix

On-demand layers (Jobs, Events, Listeners, Mail, Rules, Exceptions, Lang, Observers, Scopes):

1. Created only if they contain at least 1 file (empty folders forbidden)
2. `Lang/` is loaded by the base `ModuleServiceProvider` while the module is active
3. Detailed rules just follow Laravel conventions; no separate rule file per folder
4. Module listeners are NOT auto-discovered (bootstrap only scans `app/Listeners`); register listeners explicitly in `bootModule()` via `Event::listen`/`Event::subscribe`

## Forbidden

- No empty folders as placeholders
- No per-module package overhead (composer.json, module.json, vite config)
