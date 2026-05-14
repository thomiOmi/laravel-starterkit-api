# Auth & Permission Standards

We use **Laravel Sanctum** for authentication and **Spatie Laravel Permission** for authorization (RBAC).

## 1. Authentication (Sanctum)

- **Stateless Tokens**: All API routes must use Sanctum's token-based authentication. Never use session-based authentication for APIs.
- **Middleware**: Use `auth:sanctum` for all protected route groups.
- **Token Abilities**: Use abilities (scopes) to restrict what a token can do if multiple client types exist.
    - Example: `$request->user()->tokenCan('posts:create')`.

## 2. Authorization (RBAC)

We use the Spatie package to manage Roles and Permissions.

### Standards:
- **Gate Layer**: Prefer using the standard Laravel `Gate` and `can` middleware.
- **Form Request Authorization**: All state-mutating requests must be authorized in the `authorize()` method.
- **Naming Convention**:
    - Permissions: `resource.action` (e.g., `user.view`, `user.create`).
    - Roles: `super-admin`, `admin`, `user`.

### Implementation Example:
```php
// In Form Request
public function authorize(): bool
{
    // Authorization confirms what the user CAN do.
    return $this->user()->can('user.edit');
}
```

## 3. Super Admin
The `super-admin` role is implicitly granted all permissions via a `Gate::before` check in `AppServiceProvider`.

```php
Gate::before(function ($user, $ability) {
    return $user->hasRole('super-admin') ? true : null;
});
```

## 4. Error Handling
A failed authorization must throw an `AuthorizationException`, which the exception handler renders as a `403 Forbidden` Problem Details response.

## 5. Anti-Patterns
- ❌ Do not use Session-based authentication for API routes.
- ❌ Do not hardcode Role names in Controllers (use permissions/gates).
- ❌ Do not perform authorization checks inside Action classes; Actions receive authorized data.
- ❌ Do not return generic `401` errors for unauthorized actions (use `403`).
