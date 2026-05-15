# Business Logic & Action Standards

This project uses **Action Classes** to encapsulate business logic. We prefer a lean approach and avoid unnecessary abstraction layers like the Repository Pattern for simple CRUD operations.

## 1. Action Standards

- **Single Responsibility**: One action class per operation (e.g., `StoreUserAction`, `UpdateUserAction`).
- **Dependency Injection**: Use constructor injection for dependencies (e.g., `DatabaseManager`).
- **Eloquent Usage**: Call Eloquent models directly within Actions.
- **Transactions**: Every action that writes to the database must be wrapped in a transaction.

## 2. Implementation Example

```php
<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\User\Models\User;
use Modules\User\Payloads\StoreUserPayload;

final class StoreUserAction
{
    public function __construct(
        private readonly DatabaseManager $database
    ) {}

    public function execute(StoreUserPayload $payload): User
    {
        return $this->database->transaction(function () use ($payload) {
            /** @var User $user */
            $user = User::query()->create($payload->toArray());

            if (!empty($payload->roles)) {
                $user->assignRole($payload->roles);
            }

            return $user;
        });
    }
}
```

## 3. Dealing with Repositories

**Avoid creating new Repositories.**

The Repository Pattern is often over-engineered in Laravel. Eloquent's Active Record implementation already serves as a powerful data access layer.
- **Use Actions + Eloquent**: For most business logic and data persistence.
- **Use Query Scopes**: For reusable and complex query logic within the Model.
- **Existing Repositories**: You may encounter existing Repositories in the codebase; maintain them if necessary, but do not create new ones for standard CRUD.

## 4. Query Scopes

Instead of a Repository method like `UserRepository->findActive()`, use a Model scope:

```php
// In Modules\User\Models\User.php
public function scopeActive(Builder $query): void
{
    $query->where('is_active', true);
}

// Usage in Action or Controller
User::query()->active()->get();
```

## 5. Anti-Patterns

- ❌ Do not put complex business logic in Controllers or Models.
- ❌ Do not create "Generic" Repositories that just wrap Eloquent methods.
- ❌ Do not perform database writes without a transaction.
- ❌ Do not use Facades like `DB::transaction()`; inject `DatabaseManager` instead.
- ❌ Do not perform authorization checks inside Actions; authorization belongs in Form Requests.
