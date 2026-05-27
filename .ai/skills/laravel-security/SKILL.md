---
name: laravel-security
description: "Security standards, authorization via Spatie, and RFC-compliant API response conventions."
metadata:
  version: "1.1.0"
  triggers: "Security, Sanctum, Policy, Roles, Permissions, JsonDataResponse, ProblemResponse, Trace ID"
---

# Laravel Security & API Standards

This skill ensures every endpoint is secure, authorized, and communicates using standardized JSON envelopes.

## 1. Authentication & Authorization
- **Sanctum**: Default for API authentication.
- **Roles & Permissions**: Use Spatie `laravel-permission`. Apply `HasRoles` trait to the User model.
- **Policies**: EVERY write operation MUST be authorized via a Policy. Check permissions using `$user->can()` or `$user->hasPermissionTo()`.
- **Gates**: Register policies in `AuthServiceProvider`.

## 2. API Response Standard (Standard 2026)
Success responses MUST use `JsonDataResponse` and include a status integer. Boolean `success` is prohibited.

### Success Format (200 OK)
```json
{
    "status": 200,
    "message": "Resource created successfully",
    "data": { ... }
}
```

### Error Format (RFC 9457)
Use `ProblemResponse` for errors.
```json
{
    "status": 422,
    "message": "Validation Failed",
    "detail": "The email field is required.",
    "instance": "/api/v1/users"
}
```

## 3. Observability & Traceability
- **Trace ID**: Every request must have a `trace_id` stored in Laravel **Context**.
- **Headers**: Reflect `trace_id` in the `X-Trace-ID` response header.
- **Logging**: Ensure the `trace_id` is included in every log line via `Log::withContext()`.

## 4. Middleware Stack
- **Force JSON**: Apply `ForceJsonResponse` to all API routes to enforce `Accept: application/json`.
- **Sunset**: Use `Sunset` middleware for deprecated endpoints (RFC 8594).
- **Throttling**: Mandatory `throttle:api` on all public/authenticated routes.

## 5. Data Safety
- **Mass Assignment**: Maintain strict `$fillable` arrays. Use Payloads to map only necessary data.
- **Sensitive Data**: Use encrypted casts for sensitive fields (PII, tokens).
- **Validation**: Use FormRequests for all inputs. Never trust `request()->all()`.

## 6. Security Checklist
- [ ] Policy check implemented for the action?
- [ ] `trace_id` present in Context?
- [ ] Response uses `JsonDataResponse` or `ProblemResponse`?
- [ ] Sensitive fields filtered from API Resources?
- [ ] Rate limiting applied?
