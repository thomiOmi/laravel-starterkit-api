<?php

declare(strict_types=1);

use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-device')->plainTextToken;
});

describe('Device List', function () {
    it('lists authenticated user devices', function () {
        $this->user->createToken('second-device');

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/auth/devices')
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/auth/devices')
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

    it('marks current device in response', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/auth/devices');

        $response->assertSuccessful();
        $isCurrent = collect($response->json('data'))->pluck('is_current');
        expect($isCurrent)->toContain(true);
    });

    it('returns current device when user has one token', function () {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/auth/devices')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    });
});

describe('Delete Device', function () {
    it('deletes a specific device', function () {
        $secondToken = $this->user->createToken('to-delete');
        $deviceId = $secondToken->accessToken->getKey();

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/auth/devices/{$deviceId}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertNull($secondToken->accessToken->fresh());
    });

    it('returns 404 for non-existent device', function () {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson('/api/v1/auth/devices/non-existent')
            ->assertStatus(Response::HTTP_NOT_FOUND);
    });

    it('returns 404 for another user device', function () {
        $otherUser = User::factory()->create();
        $otherToken = $otherUser->createToken('other-device');
        $deviceId = $otherToken->accessToken->getKey();

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/auth/devices/{$deviceId}")
            ->assertStatus(Response::HTTP_NOT_FOUND);
    });

    it('requires authentication', function () {
        $this->deleteJson('/api/v1/auth/devices/123')
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    });
});

describe('Logout Other Devices', function () {
    it('deletes all other devices for the user', function () {
        $this->user->createToken('device-2');
        $this->user->createToken('device-3');

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/auth/devices/logout-others')
            ->assertStatus(Response::HTTP_NO_CONTENT);

        expect($this->user->tokens()->count())->toBe(1);
    });

    it('keeps current device active', function () {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/auth/devices/logout-others')
            ->assertStatus(Response::HTTP_NO_CONTENT);

        expect($this->user->tokens()->count())->toBe(1);
    });

    it('requires authentication', function () {
        $this->postJson('/api/v1/auth/devices/logout-others')
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    });
});
