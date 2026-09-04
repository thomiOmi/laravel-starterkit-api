<?php

declare(strict_types=1);

return [
    'name' => 'Media',

    /*
    |--------------------------------------------------------------------------
    | Storage disk
    |--------------------------------------------------------------------------
    |
    | The filesystem disk uploads are written to. Keep it on the "public"
    | disk so resolved URLs are directly reachable.
    |
    */
    'disk' => 'public',

    /*
    |--------------------------------------------------------------------------
    | Upload constraints
    |--------------------------------------------------------------------------
    |
    | Maximum upload size in kilobytes and the allowed file extensions.
    |
    */
    'max_size' => 2048,
    'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'],

    'queue' => env('MEDIA_QUEUE', false),
];
