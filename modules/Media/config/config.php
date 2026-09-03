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

    /*
    |--------------------------------------------------------------------------
    | Collections
    |--------------------------------------------------------------------------
    |
    | Per-collection visibility and single_file behaviour.
    | single_file true means only one media per model+collection,
    | the latest upload replaces the previous file.
    |
    */
    'collections' => [
        'default' => [
            'visibility' => 'private',
            'single_file' => false,
        ],
        'avatars' => [
            'visibility' => 'public',
            'single_file' => true,
        ],
        'documents' => [
            'visibility' => 'private',
            'single_file' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversions
    |--------------------------------------------------------------------------
    |
    | Named image conversions generated for image media.
    | Each conversion defines width/height/fit/format/quality.
    | Queue false means synchronous generation during upload.
    |
    */
    'queue' => env('MEDIA_QUEUE', false),

    'conversions' => [
        'thumbnail' => [
            'width' => 320,
            'height' => null,
            'fit' => 'contain',
            'format' => 'webp',
            'quality' => 80,
        ],
        'medium' => [
            'width' => 1024,
            'height' => null,
            'fit' => 'contain',
            'format' => 'webp',
            'quality' => 85,
        ],
        'large' => [
            'width' => 1920,
            'height' => null,
            'fit' => 'contain',
            'format' => 'webp',
            'quality' => 90,
        ],
    ],
];
