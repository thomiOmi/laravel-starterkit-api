# RBAC (Role-Based Access Control)

The authorization system uses the **Spatie Laravel Permission** package, integrated with the modular architecture.

## 1. Basic Concepts
- **Roles:** Groups of permissions (e.g., `super-admin`, `admin`, `user`).
- **Permissions:** Specific access rights to certain features (e.g., `user.view`).

## 2. Usage in Middleware
You can restrict route access based on roles or permissions directly in the module's route file:

```php
// Based on Role
Route::get('/admin', [AdminController::class, 'index'])->middleware('role:admin');

// Based on Permission
Route::post('/users', [UserController::class, 'store'])->middleware('permission:user.create');
```

## 3. Super Admin
Users with the `super-admin` role are automatically granted access to **all features**. This logic is set in `App\Providers\AppServiceProvider` using `Gate::before`.
