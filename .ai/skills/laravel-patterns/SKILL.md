---
name: laravel-patterns
description: Industrial-grade Laravel patterns including RFC 9457 errors, Idempotency, Stream Responses, and Modular isolation. Use when designing API contracts, cross-module communication, or complex business logic.
license: MIT
metadata:
  version: "2.0.0"
---

# Laravel Development Patterns (Industry Standard)

Production-grade patterns for scalable Laravel 13 applications.

## Key Patterns

### 1. RFC 9457 Error Handling
Always use `ProblemResponse` for 4xx and 5xx errors.
```php
return new ProblemResponse(
    type: 'https://api.example.com/errors/insufficient-funds',
    title: 'Insufficient Funds',
    status: 402,
    detail: 'Your account balance is too low to complete this transaction.'
);
```

### 2. Idempotency
Use `Idempotency-Key` header for critical POST requests.
```php
// In Controller
$key = $request->header('Idempotency-Key');
if ($cachedResponse = Idempotency::get($key)) return $cachedResponse;
```

### 3. Modular Communication
- **Synchronous:** Define Interface in `app/Contracts/Modules/{Module}Contract.php`.
- **Asynchronous:** Dispatch `Modules\{Module}\Events\{Event}` and listen in other modules.

### 4. Sparse Fieldsets
Allow clients to request specific fields to optimize payload.
```php
// In JsonResource
public function toArray(Request $request): array
{
    return $this->only($request->get('fields', ['id', 'name']));
}
```

## Reference Guide
| Topic | Reference |
|-------|-----------|
| API Standard | `references/api-standard.md` |
| Module Flow | `references/modular-flow.md` |

## Constraints
- **MUST** follow "Zero Cross-Module Model Import" rule.
- **MUST** use `SuccessResponse` or `ProblemResponse` wrappers.
- **MUST** use Property Hooks for DTO transformations.
