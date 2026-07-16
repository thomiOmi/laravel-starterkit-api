<?php

/*
|--------------------------------------------------------------------------
| API Route Versioning
|--------------------------------------------------------------------------
|
| Configure supported API versions and default version for route loading.
| Each version corresponds to a route file under the modules directory.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Supported API Versions
    |--------------------------------------------------------------------------
    |
    | List of active API versions. Each version must have a matching route
    | file at modules/ModuleName/Routes/{Version}.php.
    |
    */

    'supported_versions' => [
        'V1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default API Version
    |--------------------------------------------------------------------------
    |
    | Fallback version used when no version is explicitly requested.
    |
    */

    'default_version' => 'V1',
];
