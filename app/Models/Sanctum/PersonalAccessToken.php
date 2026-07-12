<?php

declare(strict_types=1);

namespace App\Models\Sanctum;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * @property string $id
 * @property string $name
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 */
#[Fillable([
    'name',
    'token',
    'abilities',
    'expires_at',
    'ip_address',
    'user_agent',
])]
class PersonalAccessToken extends SanctumPersonalAccessToken {}
