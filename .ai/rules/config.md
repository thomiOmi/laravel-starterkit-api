---
paths:
  - 'modules/*/config/**'
  - 'config/**'
  - config/modules.php
---

# Config

## Goal

Global config in `config/`; module config in `modules/{Module}/config/config.php` (merged by nWidart using the TitleCase module name, e.g. `config('iam.*')` via the lowercase alias).

## Rules

1. Module config is merged by the provider when the module is active
2. Config access via typed helpers (`config()->integer(...)`) to keep types intact
3. Fortify-style features array (see features rule)
4. Module activation: nWidart FileActivator stores live status in `modules_statuses.json` (keyed by lowercase alias, `{"iam": true}`); `config/modules.php` is the nWidart config (paths, providers, activator, cache). Activation is a code decision (no `MODULES_*` env override); a module is only loaded when active in the statuses file. Route loading is delegated to each module's own `RouteServiceProvider`, not the app `RouteServiceProvider`.

## Forbidden

- No `env()` outside config files
- No module config loaded while the module is inactive

## Keep modules.paths.modules lowercase (deviation from nwidart docs)
Keep `paths.modules` as `base_path('modules')` (lowercase). This deliberately deviates from the nwidart docs default `base_path('Modules')`: the lowercase folder is the repo-wide convention (docs, rules, composer merge-plugin, arch tests all reference `modules/`). Never change the config casing without renaming the folder too — the nwidart scanner globs the literal config path (`FileRepository::scan()`), and on case-sensitive filesystems (Linux CI/prod) a mismatch silently discovers zero modules, while Windows stays broken-invisible because its filesystem is case-insensitive.
