<?php

declare(strict_types=1);
use Modules\Media\Support\DisallowedExtensions;
use Modules\Media\Support\Downloaders\DefaultDownloader;
use Modules\Media\Support\FileNamer\DefaultFileNamer;
use Modules\Media\Support\FileRemover\DefaultFileRemover;
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
    | Maximum upload size in kilobytes. Allowed extensions restrict the
    | final file extension globally; null disables the allowlist and
    | defers to collection rules plus the disallowed list below.
    |
    */
    'max_size' => 2048,
    'allowed_extensions' => null,

    /*
    |--------------------------------------------------------------------------
    | Disallowed extensions
    |--------------------------------------------------------------------------
    |
    | Every extension segment of a file name is checked against this list,
    | so shell.php.jpg is rejected even though its final extension is
    | allowed. Matching is case-insensitive. Referenced from the static
    | default so config and code cannot drift; override to extend or
    | shrink it.
    |
    */
    'disallowed_extensions' => DisallowedExtensions::$defaultDisallowedExtensions,

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
    'file_namer' => DefaultFileNamer::class,

    /*
    |--------------------------------------------------------------------------
    | Path generator
    |--------------------------------------------------------------------------
    |
    | The class that contains the strategy for determining a media file's
    | path. Swap it for a custom MediaPathGenerator implementation.
    |
    */
    'path_generator' => DefaultPathGenerator::class,

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
    'url_generator' => DefaultUrlGenerator::class,

    /*
    |--------------------------------------------------------------------------
    | Versioned URLs
    |--------------------------------------------------------------------------
    |
    | When enabled, a ?v=xx query string (media updated_at timestamp) is
    | attached to generated public URLs for cache busting.
    |
    */
    'version_urls' => false,

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
    | Storage prefix
    |--------------------------------------------------------------------------
    |
    | Prefix prepended to every stored media path (originals, conversions,
    | variants). Empty by default, which keeps existing paths unchanged.
    |
    */
    'prefix' => env('MEDIA_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Conversions disk
    |--------------------------------------------------------------------------
    |
    | Disk for generated conversions. Null keeps conversions on the same
    | disk as the original file.
    |
    */
    'conversions_disk_name' => env('MEDIA_CONVERSIONS_DISK', null),

    'remote' => [
        /*
        |--------------------------------------------------------------------------
        | Extra headers
        |--------------------------------------------------------------------------
        |
        | Any extra headers included when uploading media to a remote disk.
        | Supported by S3: CacheControl, Expires, StorageClass,
        | ServerSideEncryption, Metadata, ACL, ContentEncoding.
        |
        */
        'extra_headers' => [
            // 'CacheControl' => 'max-age=604800',
        ],
    ],

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
    'media_downloader' => DefaultDownloader::class,

    /*
    |--------------------------------------------------------------------------
    | Media downloader SSL
    |--------------------------------------------------------------------------
    |
    | SSL certificates are verified by default when downloading remote
    | media. Disable only in a local environment: it is a security risk.
    |
    */
    'media_downloader_ssl' => true,

    /*
    |--------------------------------------------------------------------------
    | Downloader timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for remote media downloads.
    |
    */
    'downloader_timeout' => 10,

    /*
    |--------------------------------------------------------------------------
    | Responsive images
    |--------------------------------------------------------------------------
    |
    | Target widths generated for collections opted in via
    | MediaCollection::withResponsiveImages(). Widths larger than the
    | original file are skipped: images are never upscaled.
    |
    */
    'responsive' => [
        'widths' => [320, 640, 1024, 1600],
    ],

    /*
    |--------------------------------------------------------------------------
    | File remover
    |--------------------------------------------------------------------------
    |
    | The class that contains the strategy for removing media files.
    |
    */
    'file_remover' => DefaultFileRemover::class,
];
