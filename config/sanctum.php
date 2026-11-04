<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Sanctum Routes
    |--------------------------------------------------------------------------
    |
    | This API is bearer-token only, so the GET /sanctum/csrf-cookie route is
    | not registered. That route runs on the "web" middleware group, starts a
    | session, and sets session/XSRF cookies that no bearer-token client uses.
    | Returning false here makes SanctumServiceProvider::defineRoutes() skip
    | the route entirely.
    |
    */

    'routes' => false,

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This API is bearer-token only. Sanctum checks these guards before it
    | falls back to the bearer token, so the list is intentionally empty:
    | a user authenticated through a session guard must never be accepted
    | by "auth:sanctum" routes. The empty array is required because null
    | would fall back to the framework default ("web").
    |
    */

    'guard' => [],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. This will override any values set in the token's
    | "expires_at" attribute, but first-party sessions are not affected.
    |
    */

    'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 1440),

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    |
    | Sanctum can prefix new tokens in order to take advantage of numerous
    | security scanning initiatives maintained by open source platforms
    | that notify developers if they commit tokens into repositories.
    |
    | See: https://docs.github.com/en/code-security/secret-scanning/about-secret-scanning
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

];
