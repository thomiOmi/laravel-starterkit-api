<?php

declare(strict_types=1);
use Modules\Media\Support\Downloaders\DefaultDownloader;
use Modules\Media\Support\FileNamer\DefaultFileNamer;
use Modules\Media\Support\PathGenerator\DefaultPathGenerator;
use Modules\Media\Support\UrlGenerator\DefaultUrlGenerator;

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

    /*
    |--------------------------------------------------------------------------
    | Path generator
    |--------------------------------------------------------------------------
    |
    | The class that contains the strategy for determining a media file's
    | path. Swap it for a custom MediaPathGenerator implementation.
    |
    */
    'path_generator' => env('MEDIA_PATH_GENERATOR', DefaultPathGenerator::class),

    /*
    |--------------------------------------------------------------------------
    | Custom path generators
    |--------------------------------------------------------------------------
    |
    | Per-model path generator overrides, keyed by model class or morph
    | alias as stored in media.model_type. Models without an entry use
    | the path_generator above.
    |
    */
    'custom_path_generators' => [
        // User::class => CustomPathGenerator::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | URL generator
    |--------------------------------------------------------------------------
    |
    | When urls to files get generated, this class will be called.
    |
    */
    'url_generator' => env('MEDIA_URL_GENERATOR', DefaultUrlGenerator::class),

    /*
    |--------------------------------------------------------------------------
    | Versioned URLs
    |--------------------------------------------------------------------------
    |
    | When enabled, a ?v=xx query string (media updated_at timestamp) is
    | attached to generated public URLs for cache busting.
    |
    */
    'version_urls' => env('MEDIA_VERSION_URLS', false),

    /*
    |--------------------------------------------------------------------------
    | Temporary URL lifetime
    |--------------------------------------------------------------------------
    |
    | Default lifetime in minutes for signed temporary URLs when the
    | caller does not pass an explicit TTL.
    |
    */
    'temporary_url_default_lifetime' => env('MEDIA_TEMPORARY_URL_DEFAULT_LIFETIME', 10),

    /*
    |--------------------------------------------------------------------------
    | Media downloader
    |--------------------------------------------------------------------------
    |
    | When using addMediaFromUrl this class will fetch the remote file.
    | Swap it for a custom MediaDownloader implementation, e.g. when the
    | URL sits behind a firewall and needs extra client flags.
    |
    */
    'media_downloader' => env('MEDIA_DOWNLOADER', DefaultDownloader::class),

    /*
    |--------------------------------------------------------------------------
    | Media downloader SSL
    |--------------------------------------------------------------------------
    |
    | SSL certificates are verified by default when downloading remote
    | media. Disable only in a local environment: it is a security risk.
    |
    */
    'media_downloader_ssl' => env('MEDIA_DOWNLOADER_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | Downloader timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for remote media downloads.
    |
    */
    'downloader_timeout' => env('MEDIA_DOWNLOADER_TIMEOUT', 10),
];
