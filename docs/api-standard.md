# API Standards & Best Practices

This project follows strict standards for API development to ensure consistency and scalability.

## 1. API Versioning
Versioning is managed through the URL prefix:
- `/api/v1/`

Each module's routes are separated by version in the `modules/{Module}/Routes/v1.php` file. This allows running multiple API versions simultaneously without conflicts.

## 2. Standard JSON Response
All API responses use the `App\Traits\ApiResponser` trait to ensure a uniform format.

### Success Response (200/201):
```json
{
    "status": "success",
    "message": "Resource created successfully",
    "data": { ... },
    "meta": {
        "api_version": "v1"
    }
}
```

### Error Response (4xx/5xx):
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

## 3. Global Error Handling
Common errors are automatically handled in `bootstrap/app.php` to ensure responses are always in JSON format, including:
- **404 Not Found** (Route or Model)
- **401 Unauthenticated**
- **403 Unauthorized**
- **422 Validation Error**
- **500 Internal Server Error**

## 4. Bulk Actions
The system supports bulk actions for efficiency:
- Endpoint: `POST /api/v1/{resource}/bulk`
- Action: `delete`, `update`, `restore`, `forceDelete`.
- Logic: Implemented at the Repository layer via the `bulk()` method.

## 5. Testing Modules
You can run tests for a specific module using the following commands:
```bash
# Run tests for a specific module using Pest
./vendor/bin/pest modules/User

# Run tests using artisan filter
php artisan test --filter Modules\\User
```
