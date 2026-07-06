<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use Modules\IAM\Models\User;

describe('Logout Other Devices', function () {
    it('logs out from all other devices while keeping the current one', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $currentToken = $user->createToken('current');
        $user->createToken('other-1');
        $user->createToken('other-2');

        expect($user->tokens()->count())->toBe(3);

        expect($this->withToken($currentToken->plainTextToken)
            ->postJson('/api/v1/auth/devices/logout-others', [
                'current_password' => config('auth.default_password'),
            ]))
            ->assertStatus(204);

        expect($user->tokens()->count())->toBe(1);
    })->group('v1');

    it('fails without current_password', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $token = $user->createToken('current');

        expect($this->withToken($token->plainTextToken)
            ->postJson('/api/v1/auth/devices/logout-others', []))
            ->toBeProblemResponse(status: 422);
    })->group('v1');
});
