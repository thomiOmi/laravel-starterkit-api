<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled Modules
    |--------------------------------------------------------------------------
    |
    | Allow-list of modules that are loaded by the application, following the
    | Laravel Fortify pattern: a new module is only active once it is added
    | to this list. Modules are matched by their directory name in lowercase
    | (e.g. the "IAM" directory is "iam").
    |
    | A module that exists under modules/ but is NOT in this list is silently
    | ignored: its service provider, migrations, and routes are not loaded.
    | This also enables private modules: keep the directory on disk (and
    | listed in .gitignore) without ever registering it here for customers.
    |
    | There is intentionally no env() override - enabling a module is a code
    | decision, not an environment setting.
    |
    */

    'enabled' => [
        'iam',
        'media',
    ],

];
