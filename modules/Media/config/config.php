<?php

declare(strict_types=1);
use Modules\Media\Support\FileNamer\DefaultFileNamer;

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

    /*
    |--------------------------------------------------------------------------
    | File namer
    |--------------------------------------------------------------------------
    |
    | The class responsible for naming original, conversion, and responsive
    | image files. Swap it for a custom MediaFileNamer implementation to
    | control naming globally without touching upload code.
    |
    */
    'file_namer' => env('MEDIA_FILE_NAMER', DefaultFileNamer::class),
];
