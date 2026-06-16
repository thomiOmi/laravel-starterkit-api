<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Actions\ListDevicesAction;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

it('returns all tokens for user', function () {
    $user = User::factory()->create();
    $token1 = $user->createToken('device-1');
    $token2 = $user->createToken('device-2');

    $action = app(ListDevicesAction::class);
    $devices = $action->handle($user);

    expect($devices)
        ->toHaveCount(2)
        ->and($devices->pluck('id')->toArray())
        ->toContain($token1->accessToken->getKey(), $token2->accessToken->getKey());
});

it('returns empty collection when user has no tokens', function () {
    $user = User::factory()->create();

    $action = app(ListDevicesAction::class);
    $devices = $action->handle($user);

    expect($devices)->toHaveCount(0);
});

it('orders by last_used_at descending', function () {
    $user = User::factory()->create();
    $token1 = $user->createToken('old-device');
    $token1->accessToken->forceFill(['last_used_at' => now()->subDay()])->save();

    $token2 = $user->createToken('recent-device');
    $token2->accessToken->forceFill(['last_used_at' => now()])->save();

    $action = app(ListDevicesAction::class);
    $devices = $action->handle($user);

    expect($devices->first()->id)->toBe($token2->accessToken->getKey());
});
