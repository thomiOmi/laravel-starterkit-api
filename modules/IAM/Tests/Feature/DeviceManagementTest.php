<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use Modules\IAM\Models\User;

describe('Device Management', function () {
    it('lists active devices', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $token = $user->createToken('current');

        expect($this->withToken($token->plainTextToken)
            ->getJson('/api/v1/auth/devices'))
            ->toBeSuccessResponse()
            ->assertJsonPath('data.0.is_current', true);
    })->group('v1');

    it('logs out from another device', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $token = $user->createToken('current');
        $otherToken = $user->createToken('other');
        $id = (string) $otherToken->accessToken->id;

        expect($this->withToken($token->plainTextToken)
            ->deleteJson("/api/v1/auth/devices/{$id}"))
            ->assertStatus(204);

        expect($user->tokens()->where('id', $id)->exists())->toBeFalse();
    })->group('v1');

    it('returns 404 when deleting a non-existent device', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $token = $user->createToken('current');

        expect($this->withToken($token->plainTextToken)
            ->deleteJson('/api/v1/auth/devices/999999'))
            ->toBeProblemResponse(status: 404);
    })->group('v1');
});
