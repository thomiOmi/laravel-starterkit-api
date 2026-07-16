<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Documentation Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for problem type documentation.
    | Fallback to APP_URL/problems if not set.
    |
    */
    'docs_url' => env('ERROR_DOCS_URL', env('APP_URL').'/problems'),

    /*
    |--------------------------------------------------------------------------
    | Problem Type Slugs
    |--------------------------------------------------------------------------
    |
    | Maps error keys to URL-friendly slugs.
    | Example: 'validation' => 'https://example.com/problems/validation-failed'
    |
    */
    'types' => [
        'validation' => 'validation-failed',
        'unauthenticated' => 'authentication-required',
        'forbidden' => 'access-denied',
        'not_found' => 'resource-not-found',
        'rate_limited' => 'rate-limit-exceeded',
        'bad_request' => 'invalid-request-payload',
        'internal_error' => 'server-error',

        // The catch-all default fallback
        'default' => 'general-error',
    ],
];
