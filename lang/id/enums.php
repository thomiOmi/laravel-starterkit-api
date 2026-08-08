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
        'active' => 'Aktif',
        'inactive' => 'Nonaktif',
        'pending' => 'Menunggu',
        'suspended' => 'Ditangguhkan',
        'banned' => 'Diblokir',
    ],

    'RoleEnum' => [
        'super-admin' => 'Super Admin',
        'admin' => 'Admin',
        'user' => 'Pengguna',
    ],

    'PermissionEnum' => [
        'user_view' => 'Lihat pengguna',
        'user_create' => 'Buat pengguna',
        'user_edit' => 'Ubah pengguna',
        'user_delete' => 'Hapus pengguna',
        'user_restore' => 'Pulihkan pengguna',
        'role_view' => 'Lihat peran',
        'role_create' => 'Buat peran',
        'role_edit' => 'Ubah peran',
        'role_delete' => 'Hapus peran',
        'permission_view' => 'Lihat izin',
        'permission_create' => 'Buat izin',
        'permission_edit' => 'Ubah izin',
        'permission_delete' => 'Hapus izin',
    ],
];
