<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Model Standards
    |--------------------------------------------------------------------------
    | Configure global Eloquent Model behavior.
    */
    'model' => [
        'default_id' => 'ulid', // Options: 'ulid', 'uuid', 'integer'
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Standards
    |--------------------------------------------------------------------------
    | Rules applied when MakeModule.php runs.
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
    | Defines layer access rules for ArchTest in Phase 3.
    |
    | Each key is a layer identifier. The value is the namespace prefix that
    | arch tests use to match classes belonging to that layer.
    */
    'layers' => [
        'controllers' => 'Modules\*\Controllers',
        'actions' => 'Modules\*\Actions',
        'models' => 'Modules\*\Models',
        'requests' => 'Modules\*\Requests',
        'resources' => 'Modules\*\Resources',
        'filters' => 'Modules\*\Filters',
        'payloads' => 'Modules\*\Payloads',
        'services' => 'Modules\*\Services',
        'providers' => 'Modules\*\Providers',
        'database' => 'Modules\*\Database',
        'tests' => 'Modules\*\Tests',
        // App core layers
        'app_http' => 'App\Http',
        'app_providers' => 'App\Providers',
        'app_concerns' => 'App\Concerns',
        'app_contracts' => 'App\Contracts',
        'app_models' => 'App\Models',
        'app_console' => 'App\Console',
        'app_notifications' => 'App\Notifications',
        'app_filters' => 'App\Filters',
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
