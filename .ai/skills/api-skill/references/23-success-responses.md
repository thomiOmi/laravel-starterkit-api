# Success Response Standards

We provide consistent, structured JSON responses for all successful API operations using the `ApiResponser` trait.

## 1. JSON Envelope Structure

All successful responses should follow a standard envelope structure.

### Single Resource
```json
{
    "success": true,
    "message": "User retrieved successfully",
    "data": { ... }
}
```

### Paginated Collection
```json
{
    "success": true,
    "message": "Users retrieved successfully",
    "data": [ ... ],
    "meta": { ... },
    "links": { ... }
}
```

## 2. Transformation Layer

Always transform your models using **Eloquent Resources**. This prevents leaking database schemas and decouples the internal data structure from the API contract.

- **Single**: `new UserResource($user)`
- **Collection**: `UserResource::collection($users)`

## 3. Controller Implementation

Use the helper methods from `App\Traits\ApiResponser` in your Controllers:

```php
// For single resources
return $this->successResponse(
    data: new UserResource($user),
    message: 'User created successfully',
    status: Response::HTTP_CREATED
);

// For paginated collections (using simplePaginate)
return $this->paginateResponse(
    paginated: $users,
    resource: UserResource::class,
    message: 'Users retrieved successfully'
);
```

## 4. Key Configurations

- **No Wrapping**: We manage the `data` wrapper via our `ApiResponser` trait or globally.
- **Type-Safe Status**: Always use Symfony `Response` constants.

## 5. Anti-Patterns

- ❌ Do not return raw models or arrays from a Controller.
- ❌ Do not create ad-hoc JSON structures for success responses.
- ❌ Do not include sensitive data (like passwords) in your Resource classes.
- ❌ Do not return the same structure for errors and successes (errors must follow [05-error-handling.md](05-error-handling.md)).
