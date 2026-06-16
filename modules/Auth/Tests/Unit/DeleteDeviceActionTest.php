<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Actions\DeleteDeviceAction;
use Modules\User\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

uses(RefreshDatabase::class);

it('deletes a specific device', function () {
    $user = User::factory()->create();
    $token = $user->createToken('to-delete');
    $deviceId = $token->accessToken->getKey();

    $action = app(DeleteDeviceAction::class);
    $action->handle($user, (string) $deviceId);

    expect($user->tokens()->find($deviceId))->toBeNull();
});

it('throws not found for non-existent device', function () {
    $user = User::factory()->create();

    $action = app(DeleteDeviceAction::class);

    $action->handle($user, 'non-existent-id');
})->throws(NotFoundHttpException::class);

it('throws not found for another users device', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherToken = $otherUser->createToken('other-device');
    $deviceId = $otherToken->accessToken->getKey();

    $action = app(DeleteDeviceAction::class);

    $action->handle($user, (string) $deviceId);
})->throws(NotFoundHttpException::class);
