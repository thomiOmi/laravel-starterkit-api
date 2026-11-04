<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    /*
    |--------------------------------------------------------------------------
    | CORS Paths
    |--------------------------------------------------------------------------
    |
    | The paths that should be checked for CORS. Requests matching these paths
    | will have CORS headers applied. This API is bearer-token only, so the
    | Sanctum CSRF cookie path is not exposed to browsers.
    |
    */

    'paths' => ['api/*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed HTTP Methods
    |--------------------------------------------------------------------------
    |
    | The HTTP methods that are allowed for cross-origin requests. Setting
    | this to an empty array allows all methods.
    |
    */

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | The origins that are allowed to make cross-origin requests. You may
    | use "*" to allow all origins. Set CORS_ALLOWED_ORIGINS to the origins
    | of your separate frontend application.
    |
    */

    'allowed_origins' => explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:5173,http://localhost:8080')),

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins Patterns
    |--------------------------------------------------------------------------
    |
    | You may use regular expression patterns to match allowed origins.
    | This is useful when you need to allow dynamic subdomains.
    |
    */

    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    |
    | The headers that are allowed in cross-origin requests. The Content-Type,
    | Authorization, and X-Requested-With headers are commonly needed.
    |
    */

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'Accept', 'Origin'],

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    |
    | The headers that are exposed to the browser in the response. You may
    | expose custom headers that the frontend application needs to read.
    |
    */

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'Retry-After',
        'Deprecation',
        'Sunset',
        'Link',
        'Idempotency-Replayed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Max Age
    |--------------------------------------------------------------------------
    |
    | The number of seconds that the browser should cache the preflight
    | response. A value of 86400 (24 hours) is recommended for most APIs.
    |
    */

    'max_age' => 86400,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | When set to true, the Access-Control-Allow-Credentials header will be
    | included in the response. Bearer tokens travel in the Authorization
    | header instead of cookies, so this API does not allow credentialed
    | cross-origin requests. Enable it only if clients must send cookies.
    |
    */

    'supports_credentials' => false,

];
