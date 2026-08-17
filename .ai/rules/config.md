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
