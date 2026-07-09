<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionEnum: string
{
    case UserView = 'user.view';
    case UserCreate = 'user.create';
    case UserEdit = 'user.edit';
    case UserDelete = 'user.delete';

    case RoleView = 'role.view';
    case RoleCreate = 'role.create';
    case RoleEdit = 'role.edit';
    case RoleDelete = 'role.delete';

    case PermissionView = 'permission.view';
    case PermissionCreate = 'permission.create';
    case PermissionEdit = 'permission.edit';
    case PermissionDelete = 'permission.delete';
}
