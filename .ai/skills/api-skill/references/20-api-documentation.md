# API Documentation (Scramble) Standards

We use **Dedoc Scramble** to automatically generate OpenAPI documentation from our codebase. To ensure accurate documentation, we use PHP attributes and PHPDoc tags.

## 1. Controller Documentation

Every controller must include documentation attributes for parameters and tags.

- **`@tags`**: Group endpoints in the documentation (e.g., `/** @tags User */`).
- **`#[QueryParameter]`**: Document URL query parameters (search, pagination, filters).
- **`#[BodyParameter]`**: Document request body fields (usually applied to Form Requests).

### Example:
```php
/**
 * @tags User
 */
final class IndexController extends Controller
{
    #[QueryParameter(name: 'search', description: 'Search by name or email', type: 'string', example: 'John')]
    #[QueryParameter(name: 'role', description: 'Filter by role name', type: 'string', example: 'admin')]
    public function __invoke(Request $request, UserFilter $filter): JsonResponse
    {
        // ...
    }
}
```

## 2. Form Request Documentation

Use `#[BodyParameter]` on the Form Request class to define the expected payload.

```php
#[BodyParameter(name: 'email', description: 'User email address', required: true, example: 'user@example.com')]
#[BodyParameter(name: 'password', description: 'Min 8 characters', required: true, example: 'password123')]
final class StoreRequest extends FormRequest { ... }
```

## 3. Response Transformation

Scramble automatically infers response structures from **Eloquent Resources**. Ensure your Resources are correctly typed to help Scramble generate accurate schemas.

## 4. Anti-Patterns

- ❌ Do not leave endpoints untagged.
- ❌ Do not omit descriptions or examples for query parameters.
- ❌ Do not use manual OpenAPI YAML/JSON files; let Scramble generate it from attributes.
- ❌ Do not forget to document mandatory fields in the request body.
