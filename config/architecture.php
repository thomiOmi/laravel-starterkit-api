<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Model Standards
    |--------------------------------------------------------------------------
    | Mengatur perilaku global Eloquent Model.
    */
    'model' => [
        'default_id' => 'ulid', // Opsi: 'ulid', 'uuid', 'integer'
        'use_soft_deletes' => true,
        'audit_columns' => true, // Opsional: Tambahkan created_by/updated_by jika perlu
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Standards
    |--------------------------------------------------------------------------
    | Aturan saat MakeModule.php dijalankan.
    */
    'module' => [
        'base_path' => base_path('modules'),
        'namespace' => 'Modules',
        'enforce_action_pattern' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Layering Standards
    |--------------------------------------------------------------------------
    | Mendefinisikan akses antar layer untuk ArchTest di Tahap 3.
    */
    'layers' => [
        'repository' => 'App\Repositories',
        'service' => 'App\Services',
        'action' => 'App\Actions',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Standards
    |--------------------------------------------------------------------------
    | Global API standards.
    */
    'api' => [
        'pagination' => [
            'max_size' => 100,
        ],
    ],

];
