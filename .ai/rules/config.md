---
paths:
  - 'modules/*/Config/**'
  - 'config/**'
---

# Config

## Goal

Global config in `config/`; module config in `modules/{Module}/Config/{alias}.php` (lowercase alias from the central registry, not the TitleCase folder name).

## Rules

1. Module config is merged by the provider when the module is active
2. Config access via typed helpers (`config()->integer(...)`) to keep types intact
3. Fortify-style features array (see features rule)

## Forbidden

- No `env()` outside config files
- No module config loaded while the module is inactive
