<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enum Language Lines
    |--------------------------------------------------------------------------
    |
    | Human-readable labels for enum values, consumed by the `label()`
    | method on each enum in `app/Enums`.
    |
    */

    'UserStatusEnum' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending' => 'Pending',
        'suspended' => 'Suspended',
        'banned' => 'Banned',
    ],

    'RoleEnum' => [
        'super-admin' => 'Super Admin',
        'admin' => 'Admin',
        'user' => 'User',
    ],

    'PermissionEnum' => [
        'user_view' => 'View users',
        'user_create' => 'Create users',
        'user_edit' => 'Edit users',
        'user_delete' => 'Delete users',
        'user_restore' => 'Restore users',
        'role_view' => 'View roles',
        'role_create' => 'Create roles',
        'role_edit' => 'Edit roles',
        'role_delete' => 'Delete roles',
        'permission_view' => 'View permissions',
        'permission_create' => 'Create permissions',
        'permission_edit' => 'Edit permissions',
        'permission_delete' => 'Delete permissions',
        'media_view' => 'View media',
        'media_create' => 'Upload media',
        'media_delete' => 'Delete media',
    ],

    'MediaVisibilityEnum' => [
        'public' => 'Public',
        'private' => 'Private',
    ],
];
