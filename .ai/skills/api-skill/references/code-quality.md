# Code Quality Reference

This document defines the quality standards for writing clean, maintainable, and type-safe code.

---

## 1. Strict Typing & PHP Standards

- **Declare Strict Types**: Every file must start with `declare(strict_types=1);`.
- **Final Readonly Classes**: Actions, Payloads, and Controllers must be `final readonly`.
- **Named Arguments**: Use named arguments for better readability when methods have multiple parameters.
- **Constructor Property Promotion**: Always use property promotion to reduce boilerplate.

## 2. Documentation for Scribe

All public classes and methods must be documented using PHPDoc to enable automatic API documentation generation.

```php
/**
 * @group User Management
 * @authenticated
 *
 * @param V1\UpdateUserRequest $request The validated update request.
 * @param User $user The user model being updated.
 * @return JsonDataResponse The updated user resource.
 */
public function __invoke(UpdateUserRequest $request, User $user): JsonDataResponse
{
    // ...
}
```

## 3. Authorization (Policies & Spatie)

- **Policy First**: All authorization logic must reside in **Policies**.
- **Spatie Integration**: Use `$user->hasPermissionTo('permission-name')` or `$user->can('ability', $model)` within policies.
- **Super Admin**: Super Admin bypass should be handled centrally via `Gate::before` in `AuthServiceProvider`.
- **Form Request**: Use `$this->user()->can('update', $this->route('model'))` inside the `authorize()` method.

## 4. Database Operations

- **DatabaseManager**: Inject `Illuminate\Database\DatabaseManager` into actions to handle transactions.
- **Atomic Actions**: Actions should perform one main database operation.
- **Soft Deletes**: Use the `SoftDeletes` trait in models where data recovery is required.

## 5. Filtering Standard (BaseFilter)

Do not use external query builder packages. Use the project's `BaseFilter` system.

```php
final class UserFilter extends BaseFilter
{
    protected function apply(Builder $builder, string $key, mixed $value): void
    {
        match ($key) {
            'search' => $builder->where('name', 'like', "%{$value}%"),
            'role'   => $builder->whereHas('roles', fn($q) => $q->where('name', $value)),
            default  => null,
        };
    }
}
```
