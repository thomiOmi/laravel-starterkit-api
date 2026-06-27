<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Default rate limit for all API routes. This applies to general API
    | endpoints that do not have their own specific rate limiters.
    |
    */

    'api' => [
        'limit' => (int) env('RATE_LIMIT_API', 60),
        'decay_minutes' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for authentication-related endpoints such as login,
    | registration, password reset, etc. Limits are applied per email
    | address and per IP address to prevent brute force attacks.
    |
    */

    'auth' => [
        'limit_per_email' => (int) env('RATE_LIMIT_AUTH_PER_EMAIL', 5),
        'limit_per_ip' => (int) env('RATE_LIMIT_AUTH_PER_IP', 10),
        'decay_minutes' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authenticated User Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limit for endpoints that require an authenticated user. This
    | applies to protected API routes that are accessed with a valid
    | Bearer token or SPA session.
    |
    */

    'authenticated' => [
        'limit' => (int) env('RATE_LIMIT_AUTHENTICATED', 120),
        'decay_minutes' => 1,
    ],
];
