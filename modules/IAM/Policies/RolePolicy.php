<?php

declare(strict_types=1);

namespace Modules\IAM\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;

/**
 * Role Policy
 *
 * Authorization rules for the Role model.
 *
 * The SuperAdmin bypass is handled globally by the gate "before" hook
 * registered in AppServiceProvider, so policies only need to guard
 * against non-super-admin actors.
 */
final class RolePolicy
{
    /**
     * Determine whether the user can view the role.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->can(PermissionEnum::RoleView->value);
    }

    /**
     * Determine whether the user can create roles.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::RoleCreate->value);
    }

    /**
     * Determine whether the user can update the role.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->can(PermissionEnum::RoleEdit->value)
            && ! $this->isSuperAdminRole($role);
    }

    /**
     * Determine whether the user can delete the role.
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->can(PermissionEnum::RoleDelete->value)
            && ! $this->isSuperAdminRole($role);
    }

    /**
     * Determine whether the given role is the protected SuperAdmin role.
     */
    private function isSuperAdminRole(Role $role): bool
    {
        return $role->name === RoleEnum::SuperAdmin->value;
    }
}
