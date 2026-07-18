# Roles & Permissions (RBAC)

Uses Spatie `laravel-permission` integrated with ULID primary keys and soft deletes.

## Permissions

| Permission | Description |
|------------|-------------|
| `user.view` | View users |
| `user.create` | Create users |
| `user.edit` | Edit users |
| `user.delete` | Delete users |
| `role.view` | View roles |
| `role.create` | Create roles |
| `role.edit` | Edit roles |
| `role.delete` | Delete roles |
| `permission.view` | View permissions |
| `permission.create` | Create permissions |
| `permission.edit` | Edit permissions |
| `permission.delete` | Delete permissions |

## Roles

| Role | Description |
|------|-------------|
| `super-admin` | Unrestricted access via `Gate::before()` |
| `admin` | Full CRUD on users, roles, permissions |
| `user` | Limited read-only access |

## Authorization

FormRequest `authorize()` checks Spatie permissions:

```php
public function authorize(): bool
{
    return $this->user()?->can('user.view') ?? false;
}
```

## Endpoints

### Roles (`/api/v1/roles/`)
- `GET /roles` -- List (paginated, filterable)
- `POST /roles` -- Create (with permissions)
- `GET /roles/{role}` -- Show (with permissions)
- `PUT /roles/{role}` -- Update (name, permissions)
- `DELETE /roles/{role}` -- Soft delete
- `POST /roles/bulk/delete` -- Bulk soft delete
- `POST /roles/bulk/restore` -- Bulk restore

### Permissions (`/api/v1/permissions/`)
- `GET /permissions` -- List (paginated)
- `POST /permissions` -- Create
- `GET /permissions/{permission}` -- Show
- `PUT /permissions/{permission}` -- Update
- `DELETE /permissions/{permission}` -- Soft delete

## Models

- `Modules\IAM\Models\Role` -- extends Spatie `Role` with ULID, soft deletes, `HasFactory`
- `Modules\IAM\Models\Permission` -- extends Spatie `Permission` with ULID, soft deletes, `HasFactory`
- `Modules\IAM\Models\User` -- uses `HasRoles` trait

## Gate

Super-admin bypass is configured in `AppServiceProvider::configureSuperAdminGate()`:

```php
Gate::before(function (Identity $user) {
    return $user->hasRole(RoleEnum::SuperAdmin) ? true : null;
});
```
