<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature;

describe('Device Management', function () {
    it('lists active devices', function () {
        $user = loginAsUser();

        $this->getJson('/api/v1/auth/devices')
            ->toBeSuccessResponse()
            ->assertJsonPath('data.0.is_current', true);
    })->group('v1');

    it('logs out from another device', function () {
        $user = loginAsUser();
        $token = $user->createToken('other');
        $id = (string) $token->accessToken->id;

        $this->deleteJson("/api/v1/auth/devices/{$id}")
            ->toBeSuccessResponse();

        expect($user->tokens()->where('id', $id)->exists())->toBeFalse();
    })->group('v1');
});
