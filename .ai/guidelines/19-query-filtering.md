# Query Filtering Standards

We use a custom **Filter System** based on the `BaseFilter` class to handle complex searching, filtering, and sorting of API resources.

## 1. Core Principles

- **Separation of Concerns**: Keep query logic out of Controllers and Repositories by using dedicated Filter classes.
- **CamelCase Mapping**: Request parameters (snake_case) are automatically mapped to Filter methods (camelCase).
- **Default Sorting**: Always default to `latest()` (descending by created_at) unless a `sort_by` parameter is provided.
- **Type Safety**: Use PHP generics (`@template TModel`) for better IDE support and type checking.

## 2. Implementation Example

### The Filter Class (`modules/User/Filters/UserFilter.php`)

```php
<?php

declare(strict_types=1);

namespace Modules\User\Filters;

use App\Filters\BaseFilter;

/**
 * @extends BaseFilter<\Modules\User\Models\User>
 */
final class UserFilter extends BaseFilter
{
    /**
     * Filter users by name.
     */
    public function name(string $value): void
    {
        $this->builder->where('name', 'like', "%{$value}%");
    }

    /**
     * Filter users by role.
     */
    public function role(string $value): void
    {
        $this->builder->whereHas('roles', fn ($q) => $q->where('name', $value));
    }
}
```

### The Controller Usage

```php
public function index(Request $request, UserFilter $filter): JsonResponse
{
    $users = User::query()
        ->applyFilter($filter) // Assuming applyFilter macro or custom method
        ->paginate($request->integer('per_page', 15));

    return $this->paginateResponse($users, UserResource::class);
}
```

## 3. Standard Parameters

- `sort_by`: The column to sort by.
- `sort_direction`: `asc` or `desc` (default: `desc`).
- `page`: The current page.
- `per_page`: Number of items per page.

## 4. Anti-Patterns

- ❌ Do not build complex `where` chains directly in the Controller.
- ❌ Do not use the Spatie Query Builder package (we use our custom `BaseFilter`).
- ❌ Do not forget to type-hint the concrete Filter class in the Controller method.
- ❌ Do not perform filtering without a dedicated Filter class if more than 2 parameters are involved.
