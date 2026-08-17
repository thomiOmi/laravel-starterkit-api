---
paths:
  - 'modules/*/app/Policies/**'
---

# Policies

## Goal

Per-module authorization policies, registered via `#[UsePolicy]` on the model (single source of truth, no hidden registration in providers). Manual `Gate::policy` in providers is NOT used for modules.

## Rules

1. One policy per model when resource authorization exists
2. Registration via the `#[UsePolicy(Policy::class)]` attribute on the model
3. Use Spatie permission inside policies

## Forbidden

- No `Gate::policy` in module service providers
- No hidden authorization inside controllers
- No two sources of truth at once (Spatie permission OR Sanctum abilities, pick one per route)
