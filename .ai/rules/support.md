---
paths:
  - 'modules/*/Support/**'
  - 'app/Support/**'
---

# Support

## Goal

Purely technical utilities: self-contained, no business state, no Eloquent dependencies.

## Rules

1. Static or `final readonly`, purely technical (crypt, formatting, technical validation)
2. If it has business logic, it is a Service; if it is 1 use case, it is an Action
3. Not called directly from controllers (via Service/Action)

## Forbidden

- No Eloquent dependencies
- No domain business logic

## Example

`SocialState` (creates and verifies OAuth state tokens with expiry).
