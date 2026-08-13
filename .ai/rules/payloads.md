---
paths:
  - 'modules/*/Payloads/**'
  - 'app/Payloads/**'
---

# Payloads

## Goal

Immutable DTOs as action input: `final readonly` classes with constructor promotion, used for data crossing layers (Request to Action, queue jobs, CLI).

## Rules

1. `final readonly`, typed properties, constructor promotion
2. Validation stays in the Request; Payloads do not validate
3. Used for data crossing layers (Request to Action, queue jobs, CLI)

## Forbidden

- No payloads with validation logic
- No mutable payloads
