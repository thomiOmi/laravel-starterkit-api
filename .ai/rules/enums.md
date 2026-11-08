---
paths:
  - 'modules/*/app/Enums/**'
  - 'app/Enums/**'
---

# Enums

## Goal

Module-specific enums in `modules/{Module}/Enums/`; shared vocabulary enums (used by 2+ modules) in `app/Enums/`.

## Rules

1. Used by 1 module only: in the module. Used by 2+ modules: in app. An enum referenced by root wiring (bootstrap, providers, root migrations, global middleware) stays in `app/Enums` (ADR-0007, ADR-0045).
2. Case names are TitleCase; backing values are the stored strings (lowercase, kebab-case, or dotted). Native labels via methods (no third-party label library).
3. Cast models to enum classes

## Forbidden

- No module-specific enum living in app/Enums unless it is referenced by root wiring (rule 1)
- No shared enum living in a module
