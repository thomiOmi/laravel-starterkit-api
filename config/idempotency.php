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
    */

    'ttl' => (int) env('IDEMPOTENCY_TTL', 86400),

];
