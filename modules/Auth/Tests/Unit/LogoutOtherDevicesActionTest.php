<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Actions\LogoutOtherDevicesAction;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

it('deletes all other devices', function () {
    $password = config('auth.default_password');
    $user = User::factory()->create(['password' => $password]);
    $user->createToken('device-1');
    $current = $user->createToken('device-2');
    $user->createToken('device-3');

    $user->withAccessToken($current->accessToken);
    $action = app(LogoutOtherDevicesAction::class);
    $action->handle($user, $password);

    expect($user->tokens()->count())->toBe(1);
    expect($user->tokens()->first()->id)->toBe($current->accessToken->getKey());
});

it('keeps current device when there are no other devices', function () {
    $password = config('auth.default_password');
    $user = User::factory()->create(['password' => $password]);
    $current = $user->createToken('sole-device');

    $user->withAccessToken($current->accessToken);
    $action = app(LogoutOtherDevicesAction::class);
    $action->handle($user, $password);

    expect($user->tokens()->count())->toBe(1);
});
