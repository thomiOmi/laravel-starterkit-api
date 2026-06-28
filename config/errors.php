<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Problem Details Type URL
    |--------------------------------------------------------------------------
    |
    | The base URL used to build the "type" field in RFC 9457 Problem Details
    | responses. When set to "about:blank" (the default), the type field will
    | use "about:blank" for all problem responses. When a URL is provided, the
    | type field will be built as: {problem_type_url}/{slug}.
    |
    */
    'problem_type_url' => env('ERROR_DOCS_URL', 'about:blank'),

    /*
    |--------------------------------------------------------------------------
    | Problem Details Domain
    |--------------------------------------------------------------------------
    |
    | When problem_type_url is not set, you can optionally specify a domain here
    | to build the type URI dynamically. The final URI will be:
    | {problem_type_domain}/{problem_type_path}/{slug}.
    |
    */
    'problem_type_domain' => env('ERROR_DOCS_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Problem Details Path
    |--------------------------------------------------------------------------
    |
    | The path segment used when building the type URI dynamically from the
    | domain or app.url. Only used when problem_type_url is "about:blank".
    |
    */
    'problem_type_path' => env('ERROR_DOCS_PATH', 'problems'),

    /*
    |--------------------------------------------------------------------------
    | Error Type Slug Mapping
    |--------------------------------------------------------------------------
    |
    | Maps exception type keys to URL-friendly slugs. These slugs are appended
    | to the resolved problem type URL to create the full "type" URI for each
    | problem response.
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
    ],
];
