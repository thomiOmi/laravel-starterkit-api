# Response & Error Standards

We follow strict standards for API responses and error handling to ensure consistency for API consumers.

## 1. Successful Responses

- **Eloquent Resources**: Always use Laravel's JsonResources to transform data.
- **Success Helper**: Use the `successResponse` or `paginateResponse` helper from `ApiResponser` trait in Controllers.

## 2. Error Handling (RFC 9457)

All error responses must follow the **Problem Details for HTTP APIs (RFC 9457)** standard.

### ProblemResponse Class
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

The exception handler in `bootstrap/app.php` should render all exceptions as Problem Details:

| Exception | Status | Problem Type Slug |
|---|---|---|
| `ValidationException` | `422` | `validation-error` |
| `AuthenticationException` | `401` | `unauthenticated` |
| `AuthorizationException` | `403` | `forbidden` |
| `ModelNotFoundException` | `404` | `not-found` |
| `Throwable` (Catch-all) | `500` | `server-error` |

### Implementation Example:
```php
$exceptions->render(function (ValidationException $e, Request $request): ProblemResponse {
    return new ProblemResponse(
        type:   'https://example.com/problems/validation-error',
        title:  'Validation Error',
        status: Response::HTTP_UNPROCESSABLE_ENTITY,
        detail: 'The given data was invalid.',
        errors: $e->errors(),
    );
});
```

## 4. HTTP Constants
Always use `Symfony\Component\HttpFoundation\Response` constants for all status codes.

## 5. Anti-Patterns

- ❌ Do not return raw Eloquent models or arrays.
- ❌ Do not use bare integers like `404` or `500`.
- ❌ Do not return HTML error pages for API routes.
- ❌ Do not use ad-hoc JSON structures for errors; always follow the Problem Details shape.
