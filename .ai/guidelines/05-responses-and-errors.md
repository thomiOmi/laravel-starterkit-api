# Response & Error Standards

We follow strict standards for API responses and error handling to ensure consistency for API consumers.

## 1. Successful Responses

- **Eloquent Resources**: Always use Laravel's JsonResources to transform data.
- **Json Wrapping**: Use `JsonResource::withoutWrapping()` during development to ensure consistent structure.
- **Success Helper**: Use the `successResponse` or `paginateResponse` helper from `ApiResponser` trait in Controllers.

### Development Mode Setup (Reference)
*Note: This is an instruction for environment setup, not code changes.*
To disable wrapping in development, add this to `AppServiceProvider::boot()`:

```php
if (! app()->isProduction()) {
    JsonResource::withoutWrapping();
}
```

## 2. Error Handling (RFC 9457)

All error responses must follow the **Problem Details for HTTP APIs (RFC 9457)** standard.

### Expected Error Shape:
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

### Problem Types:
- `validation-error` (422)
- `unauthenticated` (401)
- `forbidden` (403)
- `not-found` (404)
- `server-error` (500)

## 3. Implementation Guidelines

- **Force JSON**: Ensure the API always returns JSON, even for low-level errors, by using a `ForceJsonResponse` middleware (sets `Accept: application/json`).
- **HTTP Constants**: Use `Symfony\Component\HttpFoundation\Response` constants for all status codes.

### Example Controller Response:
```php
return $this->successResponse(
    data: new UserResource($user),
    message: 'User retrieved successfully',
    status: Response::HTTP_OK
);
```

## 4. Anti-Patterns

- ❌ Do not return raw Eloquent models or arrays.
- ❌ Do not use bare integers like `404` or `500`.
- ❌ Do not return HTML error pages for API routes.
- ❌ Do not use ad-hoc JSON structures for errors; always follow the Problem Details shape.
