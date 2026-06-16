<?php

declare(strict_types=1);

return [
    'api' => [
        'limit' => (int) env('RATE_LIMIT_API', 60),
        'decay_minutes' => 1,
    ],

    'auth' => [
        'limit_per_email' => (int) env('RATE_LIMIT_AUTH_PER_EMAIL', 5),
        'limit_per_ip' => (int) env('RATE_LIMIT_AUTH_PER_IP', 10),
        'decay_minutes' => 1,
    ],

    'authenticated' => [
        'limit' => (int) env('RATE_LIMIT_AUTHENTICATED', 120),
        'decay_minutes' => 1,
    ],
];
