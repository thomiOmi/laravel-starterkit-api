---
paths:
  - 'app/Http/Responses/**'
---

# Responses

## Goal

Shared response envelopes in `app/Http/Responses/` are the single response contract for the whole API: `SuccessResponse` renders `{status, title?, detail?, data, meta?}` and `ProblemResponse` renders RFC 9457 problem details `{type, title, status, detail, timestamp}`. `app/Http/Responses` is a global contract and is not mirrored into modules.

## Rules

1. All API responses use the shared envelopes; never add a `success` boolean
2. Errors always go through `ProblemResponse` (or the exception handler) with Content-Type `application/problem+json`; 4xx/5xx map to problem details automatically via the handler (see error-handling rule 3)
3. Error type comes from `config/errors.php` typeKey
4. Date format for all response fields: `Y-m-d H:i:s`
5. Resources belong to the module; the app-wide envelope shape is global

## Forbidden

- No resource altering the global envelope structure
- No non-contract responses from controllers

## Example

```text
SuccessResponse: {status, title?, detail?, data, meta?}
ProblemResponse: {type, title, status, detail, timestamp} (RFC 9457, application/problem+json)
```
