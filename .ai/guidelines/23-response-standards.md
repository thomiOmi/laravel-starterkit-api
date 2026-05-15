# Response Standards

We provide consistent, structured JSON responses for all successful API operations.

## 1. Response Structure

All successful responses should follow a standard "envelope" structure using the `ApiResponser` trait.

### Success Response (Single Resource)
```json
{
    "success": true,
    "message": "User retrieved successfully",
    "data": {
        "id": "...",
        "name": "John Doe",
        "email": "john@example.com"
    }
}
```

### Paginated Response (Collection)
```json
{
    "success": true,
    "message": "Users retrieved successfully",
    "data": [ ... ],
    "meta": {
        "current_page": 1,
        "from": 1,
        "path": "...",
        "per_page": 15,
        "to": 15
    },
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    }
}
```

## 2. Using Eloquent Resources

Always transform your models using **Eloquent Resources**. This prevents leaking internal database structures and allows for easy data manipulation.

- **Single**: `new UserResource($user)`
- **Collection**: `UserResource::collection($users)`

## 3. Implementation in Controllers

Use the helper methods from `App\Traits\ApiResponser` to ensure consistency:

```php
// For single resources
return $this->successResponse(
    data: new UserResource($user),
    message: 'User created successfully',
    status: Response::HTTP_CREATED
);

// For paginated collections
return $this->paginateResponse(
    paginated: $users,
    resource: UserResource::class,
    message: 'Users retrieved successfully'
);
```

## 4. Anti-Patterns

- ❌ Do not return raw models or arrays from a Controller.
- ❌ Do not create ad-hoc JSON structures for success responses.
- ❌ Do not include sensitive data (like passwords) in your Resource classes.
- ❌ Do not use the `data` wrapper inside the Resource class if `JsonResource::withoutWrapping()` is active (check project-specific settings).
- ❌ Do not return the same structure for errors and successes (errors must follow RFC 9457).
