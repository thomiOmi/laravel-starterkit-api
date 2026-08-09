<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionEnum: string
{
    case UserView = 'user.view';
    case UserCreate = 'user.create';
    case UserEdit = 'user.edit';
    case UserDelete = 'user.delete';
    case UserRestore = 'user.restore';

    case RoleView = 'role.view';
    case RoleCreate = 'role.create';
    case RoleEdit = 'role.edit';
    case RoleDelete = 'role.delete';

    case PermissionView = 'permission.view';
    case PermissionCreate = 'permission.create';
    case PermissionEdit = 'permission.edit';
    case PermissionDelete = 'permission.delete';

    case MediaView = 'media.view';
    case MediaCreate = 'media.create';
    case MediaDelete = 'media.delete';

    /**
     * Get the human-readable, localized label for this permission.
     *
     * Dots in the value are replaced with underscores so the key stays
     * compatible with the `__()` dot notation lookup.
     */
    public function label(): string
    {
        return __('enums.'.basename(self::class).'.'.str_replace('.', '_', $this->value));
    }
}
