<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Modules\IAM\Models\User;

describe('Logout Other Devices', function () {
    $password = 'test-password';

    it('logs out from all other devices while keeping the current one', function () use ($password) {
        $user = User::factory()->create(['password' => Hash::make($password), 'email_verified_at' => now()]);
        $currentToken = $user->createToken('current');
        $user->createToken('other-1');
        $user->createToken('other-2');

        expect($user->tokens()->count())->toBe(3);

        expect($this->withToken($currentToken->plainTextToken)
            ->postJson('/api/v1/auth/devices/logout-others', [
                'current_password' => $password,
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
