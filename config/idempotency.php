<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Idempotency Keys
    |--------------------------------------------------------------------------
    |
    | TTL (seconds) for stored idempotent responses. Successful mutating
    | responses are kept this long so a client replaying the same request
    | with the same Idempotency-Key receives the original response instead
    | of a duplicate write.
    |
    | Recommended: 3600 (1 hour) to 86400 (24 hours).
    | Shorter TTL = faster key reuse, longer TTL = more protection against replay.
    |
    */

    'ttl' => (int) env('IDEMPOTENCY_TTL', 86400),

    /*
    |--------------------------------------------------------------------------
    | Lock Configuration
    |--------------------------------------------------------------------------
    |
    | Lock timeout determines how long a lock is held before auto-release.
    | Wait timeout is how long a duplicate request will wait for the lock
    | before giving up with a 409 Conflict.
    |
    */

    'lock_timeout' => (int) env('IDEMPOTENCY_LOCK_TIMEOUT', 30),
    'wait_timeout' => (int) env('IDEMPOTENCY_WAIT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Size Limits
    |--------------------------------------------------------------------------
    |
    | Requests or responses larger than these limits (in bytes) will not
    | participate in idempotency caching to prevent memory exhaustion.
    |
    */

    'max_body_size' => (int) env('IDEMPOTENCY_MAX_BODY_SIZE', 1048576),
    'max_response_size' => (int) env('IDEMPOTENCY_MAX_RESPONSE_SIZE', 2097152),

];
