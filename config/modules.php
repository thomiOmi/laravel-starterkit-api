<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Central Module Registry
    |--------------------------------------------------------------------------
    |
    | Single source of truth for module activation and build-time feature
    | toggles, following the Laravel Fortify pattern (features array).
    | A module is only active once it is registered here with active => true.
    |
    | The key is the lowercase module alias (e.g. "iam" for the "IAM"
    | directory). Boot order between modules follows declaration order.
    |
    | A module that exists under modules/ but is NOT registered here (or is
    | registered as inactive) is fully inert: its service provider, config,
    | migrations, and routes are never loaded. This also enables private
    | modules: keep the directory on disk (and in .gitignore) without ever
    | registering it here for customers.
    |
    | There is intentionally no env() override - activating a module or a
    | build-time feature is a code decision, not an environment setting.
    |
    */

    'modules' => [
        'iam' => [
            'active' => true,
            'features' => [
                'self-registration' => true,
            ],
        ],

        'media' => [
            'active' => true,
            'features' => [],
        ],

        'organization' => [
            'active' => false,
            'features' => [
                'multi-tenancy' => false,
            ],
        ],
    ],

];
