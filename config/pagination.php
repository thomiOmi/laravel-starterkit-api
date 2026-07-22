<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Per Page
    |--------------------------------------------------------------------------
    |
    | This value defines the default number of items returned per page when
    | the client does not specify a page size in the request.
    |
    */

    'default_per_page' => 10,

    /*
    |--------------------------------------------------------------------------
    | Minimum Per Page
    |--------------------------------------------------------------------------
    |
    | The minimum number of items allowed per page. Clients requesting a
    | smaller page size will be clamped to this value.
    |
    */

    'min_per_page' => 1,

    /*
    |--------------------------------------------------------------------------
    | Maximum Per Page
    |--------------------------------------------------------------------------
    |
    | The maximum number of items allowed per page. Clients requesting a
    | larger page size will be clamped to this value.
    |
    */

    'max_per_page' => 100,

];
