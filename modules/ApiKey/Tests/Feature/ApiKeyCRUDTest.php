<?php

declare(strict_types=1);

namespace Modules\ApiKey\Tests\Feature;

use Modules\ApiKey\Models\ApiKey;
use Modules\User\Models\User;

test('user can list their api keys', function () {
    $user = User::factory()->create();
    ApiKey::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson('/api/v1/api-keys');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('user can create an api key', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/v1/api-keys', [
            'name' => 'My Test Key',
            'abilities' => ['user.view'],
            'ip_whitelist' => ['127.0.0.1'],
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'api_key' => ['id', 'name', 'secret_prefix'],
                'plain_text_key',
            ],
        ]);

    $this->assertDatabaseHas('api_keys', [
        'user_id' => $user->id,
        'name' => 'My Test Key',
    ]);
});

test('user can revoke an api key', function () {
    $user = User::factory()->create();
    $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->deleteJson("/api/v1/api-keys/{$apiKey->id}");

    $response->assertStatus(200);

    // Soft delete check
    $this->assertSoftDeleted('api_keys', ['id' => $apiKey->id]);
});
