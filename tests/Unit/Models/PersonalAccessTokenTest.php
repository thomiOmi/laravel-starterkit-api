<?php

declare(strict_types=1);

use App\Models\Sanctum\PersonalAccessToken;

test('has fillable attributes', function () {
    $token = new PersonalAccessToken;

    expect($token->getFillable())->toContain('ip_address', 'user_agent');
});

test('has casts defined', function () {
    $token = new PersonalAccessToken;
    $casts = $token->getCasts();

    expect($casts['abilities'])->toBe('json');
    expect($casts['last_used_at'])->toBe('datetime');
    expect($casts['expires_at'])->toBe('datetime');
});
