---
name: laravel-boost-security
description: "Security standards, authentication, and API response conventions."
metadata:
  version: "1.0.0"
  triggers: "Sanctum, Policy, JsonDataResponse, ProblemResponse, trace_id"
---

# Laravel Boost Security & API

Ensures secure development and standardized API communication.

## Security
- **Authentication**: Use `auth:sanctum` middleware.
- **Authorization**: Mandatory Policy checks using `$user->can()` or `$user->hasPermissionTo()`.
- **Validation**: Use `FormRequest` with specific `authorize()` logic.

## API Standards
- **Success**: `JsonDataResponse` -> `{status, message, data}`.
- **Error**: `ProblemResponse` (RFC 9457).
- **Traceability**: Use Laravel **Context** for `trace_id` in logs and `X-Trace-ID` header.

## Example Response
```php
return new JsonDataResponse(
    data: new UserResource($user),
    message: __('User retrieved successfully')
);
```
