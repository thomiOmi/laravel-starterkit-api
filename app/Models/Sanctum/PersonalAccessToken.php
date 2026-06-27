<?php

declare(strict_types=1);

namespace App\Models\Sanctum;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

#[Fillable([
    'name',
    'token',
    'abilities',
    'expires_at',
    'ip_address',
    'user_agent',
])]
class PersonalAccessToken extends SanctumPersonalAccessToken {}
