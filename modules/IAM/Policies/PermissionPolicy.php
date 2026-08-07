<?php

declare(strict_types=1);

namespace Modules\IAM\Policies;

use App\Enums\PermissionEnum;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\User;

/**
 * Permission Policy
 *
 * Authorization rules for the Permission model.
 *
 * The SuperAdmin bypass is handled globally by the gate "before" hook
 * registered in AppServiceProvider, so policies only need to guard
 * against non-super-admin actors.
 */
final class PermissionPolicy
{
    /**
     * Determine whether the user can view the permission.
     */
    public function view(User $user, Permission $permission): bool
    {
        return $user->can(PermissionEnum::PermissionView->value);
    }

    /**
     * Determine whether the user can create permissions.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::PermissionCreate->value);
    }

    /**
     * Determine whether the user can update the permission.
     */
    public function update(User $user, Permission $permission): bool
    {
        return $user->can(PermissionEnum::PermissionEdit->value);
    }

    /**
     * Determine whether the user can delete the permission.
     */
    public function delete(User $user, Permission $permission): bool
    {
        return $user->can(PermissionEnum::PermissionDelete->value);
    }
}
