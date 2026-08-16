<?php

declare(strict_types=1);

namespace Modules\IAM\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Modules\IAM\Models\User;

/**
 * User Policy
 *
 * Authorization rules for the User model.
 *
 * The SuperAdmin bypass is handled globally by the gate "before" hook
 * registered in AppServiceProvider, so policies only need to guard
 * against non-super-admin actors.
 */
final class UserPolicy
{
    /**
     * Determine whether the user can view the target user.
     */
    public function view(User $user, User $targetUser): bool
    {
        return $user->is($targetUser) || $user->can(PermissionEnum::UserView->value);
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::UserCreate->value);
    }

    /**
     * Determine whether the user can update the target user.
     */
    public function update(User $user, User $targetUser): bool
    {
        if ($targetUser->hasRole(RoleEnum::SuperAdmin->value)) {
            return false;
        }

        return $user->is($targetUser) || $user->can(PermissionEnum::UserEdit->value);
    }

    /**
     * Determine whether the user can delete the target user.
     */
    public function delete(User $user, User $targetUser): bool
    {
        return ! $user->is($targetUser)
            && $user->can(PermissionEnum::UserDelete->value)
            && ! $targetUser->hasRole(RoleEnum::SuperAdmin->value);
    }
}
