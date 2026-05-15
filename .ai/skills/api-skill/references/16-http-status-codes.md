# HTTP Status Code Standards

To ensure clarity and standardization, we always use Symfony's `Response` constants for HTTP status codes. **Never use bare integers.**

## 1. Common API Status Codes

| Scenario | Symfony Constant | Integer |
|---|---|---|
| **Successful Read/Update** | `Response::HTTP_OK` | 200 |
| **Resource Created** | `Response::HTTP_CREATED` | 201 |
| **Accepted for Background Processing** | `Response::HTTP_ACCEPTED` | 202 |
| **Successful Delete (No Body)** | `Response::HTTP_NO_CONTENT` | 204 |
| **Authentication Failed** | `Response::HTTP_UNAUTHORIZED` | 401 |
| **Authorization Failed** | `Response::HTTP_FORBIDDEN` | 403 |
| **Resource Not Found** | `Response::HTTP_NOT_FOUND` | 404 |
| **Validation Failed** | `Response::HTTP_UNPROCESSABLE_ENTITY` | 422 |
| **Server Error** | `Response::HTTP_INTERNAL_SERVER_ERROR` | 500 |

## 2. Implementation

Always import the Response class:
`use Symfony\Component\HttpFoundation\Response;`

### Example:
```php
return $this->successResponse(
    data: new UserResource($user),
    message: 'User created successfully',
    status: Response::HTTP_CREATED
);
```

## 3. Anti-Patterns

- ❌ Do not use integers like `200`, `201`, or `404` directly in your code.
- ❌ Do not return `200 OK` for a newly created resource (use `201`).
- ❌ Do not return `200 OK` if the request was accepted but not yet finished (use `202`).
- ❌ Do not return `400 Bad Request` for validation errors (use `422`).
