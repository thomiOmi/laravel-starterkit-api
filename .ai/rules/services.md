---
paths:
  - 'modules/*/app/Services/**'
---

# Services

## Goal

Shared business logic across use cases: business logic used by 2+ call sites or consolidating complex flows across use cases. Distinction from Actions: Action = 1 use case; Service = shared logic.

## Rules

1. `final readonly`, dependencies injected via constructor
2. Never receive `Request`
3. May call Actions and models
4. Minimum 2 call sites or a complex flow; 1 call site should be an Action

## Forbidden

- No service for a single call site
- No service calling controller/HTTP layer

## Example

`UserAuthorizationService` (determines token abilities and creates the access token, used by both login and register).
