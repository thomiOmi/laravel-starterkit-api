# Auth & Permission Standards

We use **Laravel Sanctum** for authentication and **Spatie Laravel Permission** for authorization (RBAC).

## 1. Authentication (Sanctum)

- **Stateless**: All API routes must use Sanctum's token-based authentication.
- **Middleware**: Use `auth:sanctum` for all protected routes.
- **Guard**: The system uses a standard User model for authentication.

## 2. Authorization (Permissions & Roles)

We use the Spatie package to manage Roles and Permissions.

### Standards:
- **Gate Layer**: Prefer using the standard Laravel `Gate` and `can` middleware.
- **Form Request Authorization**: Perform authorization checks inside the `authorize()` method of Form Requests.
- **Naming Convention**:
    - Permissions: `resource.action` (e.g., `user.view`, `user.create`, `post.delete`).
    - Roles: `super-admin`, `admin`, `user`.

### Example Form Request Authorization:
```php
public function authorize(): bool
{
    // Check if the user has a specific permission
    return $this->user()->can('user.edit');
}
```

### Route Middleware:
```php
Route::put('/{user}', UpdateController::class)
    ->middleware('can:user.edit')
    ->name('update');
```

## 3. Super Admin
The `super-admin` role is implicitly granted all permissions via a `Gate::before` check in `AppServiceProvider`. You do not need to manually assign every permission to a super-admin.

## 4. Policy vs Middleware
- Use **Middleware** (`can:permission`) for simple endpoint access control.
- Use **Policies** for complex logic that depends on the specific model instance (e.g., "only the author can edit this post").

## 5. Anti-Patterns
- ❌ Do not use Session-based authentication for API routes.
- ❌ Do not hardcode Role names in Controllers or Actions (use permissions instead).
- ❌ Do not perform authorization checks inside Action classes.
- ❌ Do not use manual `if ($user->hasRole(...))` checks if a permission check is more appropriate.
