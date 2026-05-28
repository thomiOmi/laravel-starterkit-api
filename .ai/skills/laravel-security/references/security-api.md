# Security & API Standards

This document defines the security protocols and API communication standards for the project.

## 1. Authentication & Authorization
- **Sanctum**: Use for all API authentication. Routes must be protected by the `auth:sanctum` middleware.
- **Spatie Permission**:
    - Use `HasRoles` trait in the `User` model.
    - Check permissions in Laravel **Policies** using `$user->can()` or `$user->hasPermissionTo()`.
    - Authorization MUST happen in the `authorize()` method of FormRequests or explicitly at the start of an Action.

## 2. API Response Formats (Standard 2026)
Boolean `success` is prohibited. Use integer `status` and standardized envelopes.

### Success: `JsonDataResponse`
```json
{
    "status": 200,
    "message": "Operation successful",
    "data": {
        "id": 1,
        "name": "Example"
    }
}
```

### Error: `ProblemResponse` (RFC 9457)
```json
{
    "status": 422,
    "message": "Validation Failed",
    "detail": "The email field is already taken.",
    "errors": {
        "email": ["The email field is already taken."]
    }
}
```

## 3. Observability & Traceability
- **Trace ID**: Every request is assigned a `trace_id`.
- **Laravel Context**: Use `Context::add('trace_id', $id)` to store the identifier.
- **Logging**: Trace IDs must be shared with Monolog context via `Log::withContext()`.
- **Response Header**: The `X-Trace-ID` header must be present in every API response.

## 4. Mandatory Middleware
- **ForceJsonResponse**: Forces `Accept: application/json` on all requests.
- **Sunset**: Formats the `Sunset` header according to RFC 8594 for deprecated endpoints.
- **Throttle**: Use `throttle:api` on all routes to prevent abuse.

## 5. Data Privacy
- **Encrypted Casts**: Use `encrypted` casts for PII (Personally Identifiable Information) and secret tokens in Eloquent models.
- **Sensitive Fields**: Ensure fields like `password`, `remember_token`, and `api_token` are hidden in Models and excluded from API Resources.
