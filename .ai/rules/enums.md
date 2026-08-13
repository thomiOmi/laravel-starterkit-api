---
paths:
  - 'modules/*/Enums/**'
  - 'app/Enums/**'
---

# Enums

## Goal

Module-specific enums in `modules/{Module}/Enums/`; shared vocabulary enums (used by 2+ modules) in `app/Enums/`.

## Rules

1. Used by 1 module only: in the module. Used by 2+ modules: in app
2. Values in TitleCase; native labels via methods (no third-party label library)
3. Cast models to enum classes

## Forbidden

- No module-specific enum living in app/Enums
- No shared enum living in a module
