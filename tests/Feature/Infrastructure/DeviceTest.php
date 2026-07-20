<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\IAM\Models\User;

uses(RefreshDatabase::class);

describe('Device List', function () {
    it('returns the authenticated user devices', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $token = $user->createToken('my-device', ['*']);
        $this->withToken($token->plainTextToken);

        $response = $this->getJson('/api/v1/auth/devices');

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('my-device');
    })->group('v1');
})->group('v1');

describe('Device Delete', function () {
    it('deletes a device', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $createdToken = $user->createToken('to-be-deleted', ['*']);
        $this->withToken($createdToken->plainTextToken);

        $deviceId = $createdToken->accessToken->getKey();

        $response = $this->deleteJson("/api/v1/auth/devices/{$deviceId}");

        expect($response)->toBeSuccessResponse(status: 200, title: 'Device deleted successfully');
        expect(PersonalAccessToken::find($deviceId))->toBeNull();
    })->group('v1');

    it('returns 404 when deleting non-existent device', function () {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $token = $user->createToken('my-device', ['*']);
        $this->withToken($token->plainTextToken);

        $response = $this->deleteJson('/api/v1/auth/devices/999999');

        expect($response)->toBeProblemResponse(status: 404);
    })->group('v1');
})->group('v1');

describe('Logout Other Devices', function () {
    it('logs out other devices while keeping current', function () {
        $password = 'known-password';
        $user = User::factory()->create([
            'name' => 'Device Test User',
            'email' => 'device-test@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make($password),
        ]);

        $user->tokens()->create(['name' => 'Other 1', 'token' => hash('sha256', Str::random(40))]);
        $user->tokens()->create(['name' => 'Other 2', 'token' => hash('sha256', Str::random(40))]);

        expect($user->tokens()->count())->toBe(2);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'device-test@example.com',
            'password' => $password,
        ]);

        $token = $loginResponse->json('data.access_token');

        expect($user->tokens()->count())->toBe(3);

        $response = $this->withToken($token)->postJson('/api/v1/auth/devices/logout-others', [
            'current_password' => $password,
        ]);

        expect($response)->toBeSuccessResponse(status: 200, title: 'Other devices logged out successfully.');
        expect($user->tokens()->count())->toBe(1);
    })->group('v1');

    it('rejects logout-others with wrong password', function () {
        $password = 'known-password';
        $user = User::factory()->create([
            'name' => 'Wrong Password User',
            'email' => 'wrong-pw@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make($password),
        ]);

        $token = $user->createToken('my-device', ['*']);
        $this->withToken($token->plainTextToken);

        $response = $this->postJson('/api/v1/auth/devices/logout-others', [
            'current_password' => 'wrong-password',
        ]);

        expect($response)->toBeProblemResponse(status: 422);
    })->group('v1');
})->group('v1');
