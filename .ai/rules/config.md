---
paths:
  - 'modules/*/Config/**'
  - 'config/**'
  - config/modules.php
---

# Config

## Goal

Global config in `config/`; module config in `modules/{Module}/Config/{alias}.php` (lowercase alias from the central registry, not the TitleCase folder name).

## Rules

1. Module config is merged by the provider when the module is active
2. Config access via typed helpers (`config()->integer(...)`) to keep types intact
3. Fortify-style features array (see features rule)
4. Module registry shape + live activation: `config/modules.php` is a central registry keyed by lowercase module alias (`iam`, `media`, `organization`), each value `['active' => bool, 'features' => [...]]`, not a list of names. Activation is a code decision (no `MODULES_*` env override); `active` defaults to false (opt-in). `ModuleLoaderServiceProvider` registers a provider only when active; route loading is delegated to each module's own provider (extends base `ModuleServiceProvider`), not `RouteServiceProvider`.

## Forbidden

- No `env()` outside config files
- No module config loaded while the module is inactive
