---
name: laravel-security
description: "Expert security best practices, authorization standards, and RFC-compliant API communication."
metadata:
  version: "1.3.0"
  triggers: "Security, Sanctum, Policy, Role, Permission, DataResponse, ProblemResponse, Trace ID"
---

# Laravel Security & API

Enforces a secure-by-default environment with standardized responses.

## Instructions

- Use `auth:sanctum` for all protected routes.
- Implement mandatory Policy checks for all database operations.
- Use `DataResponse` for successes and `ProblemResponse` for errors.
- Ensure `trace_id` is propagated through logs and headers.
- Refer to `references/security-api.md` for full technical details.

## Middleware

- `force.json`: Always required.
- `sunset`: Use for deprecations.
- `throttle:api`: Mandatory rate limiting.
