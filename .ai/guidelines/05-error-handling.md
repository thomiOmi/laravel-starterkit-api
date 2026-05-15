# Error Handling Standards (RFC 9457)

All error responses in this project must follow the **Problem Details for HTTP APIs (RFC 9457)** standard. This ensures that API consumers receive consistent, machine-readable error information.

## 1. Problem Details Structure

Every error response must have a `Content-Type: application/problem+json` header and follow this shape:

```json
{
    "type": "https://example.com/problems/validation-error",
    "title": "Validation Error",
    "status": 422,
    "detail": "The given data was invalid.",
    "errors": {
        "email": ["The email has already been taken."]
    }
}
```

## 2. ProblemResponse Class

We use a dedicated `Responsable` class to ensure consistent error formatting:

```php
final readonly class ProblemResponse implements Responsable
{
    public function __construct(
        private string $type,
        private string $title,
        private int    $status,
        private string $detail,
        private array  $errors = [],
    ) {}

    public function toResponse($request): JsonResponse
    {
        return new JsonResponse(
            data: array_filter([
                'type'   => $this->type,
                'title'  => $this->title,
                'status' => $this->status,
                'detail' => $this->detail,
                'errors' => $this->errors ?: null,
            ]),
            status:  $this->status,
            headers: ['Content-Type' => 'application/problem+json'],
        );
    }
}
```

## 3. Exception Mapping

The exception handler in `bootstrap/app.php` should render all exceptions using `ProblemResponse`:

| Exception | Status | Problem Type Slug |
|---|---|---|
| `ValidationException` | `422` | `validation-error` |
| `AuthenticationException` | `401` | `unauthenticated` |
| `AuthorizationException` | `403` | `forbidden` |
| `ModelNotFoundException` | `404` | `not-found` |
| `Throwable` (Catch-all) | `500` | `server-error` |

## 4. Anti-Patterns

- ❌ Do not return raw arrays or `JsonResponse` for errors; use `ProblemResponse`.
- ❌ Do not return HTML error pages for API routes.
- ❌ Do not use ad-hoc JSON structures for errors.
- ❌ Do not use bare integers for status codes (use `Response` constants).
