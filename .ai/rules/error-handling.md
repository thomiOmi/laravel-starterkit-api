---
paths:
  - 'bootstrap/app.php'
  - 'modules/*/app/Actions/**'
  - 'modules/*/app/Http/Controllers/**'
---

# Error Handling & Exception Helpers

## Goal

Errors are communicated via exceptions and mapped to `ProblemResponse` (RFC 9457) by the handler in `bootstrap/app.php`. Laravel `abort*`/`throw*` helpers are used per layer to avoid try/catch boilerplate.

## Rules

1. HTTP layer (controllers, middleware, requests): `abort`/`abort_if`/`abort_unless` for HTTP conditions (403, 404, 409); status follows the handler mapping
2. Domain layer (Action, Payload, Support): `throw_if`/`throw_unless` + domain exceptions: `InvalidArgumentException` (mapped to 422), `ModelNotFoundException` (mapped to 404, for ownership checks), custom exceptions in `Exceptions/` when a special status/type is needed
3. Exception-to-ProblemResponse mapping only in the handler; controllers do not write manual error responses
4. Error messages via translation keys `__()`, not hardcoded strings
5. Required lookups use `findOrFail`/`firstOrFail`/`valueOrFail` (throws ModelNotFoundException to 404); do not use `updateOrFail`/`deleteOrFail`/`saveOrFail` as lookup replacements (all return false silently when the model does not exist)

## Forbidden

- No `abort`/`abort_if`/`abort_unless` in the domain layer (Actions, Payloads, Support)
- No try/catch in controllers to map errors (the handler handles it)
- No hardcoded error messages in throws
