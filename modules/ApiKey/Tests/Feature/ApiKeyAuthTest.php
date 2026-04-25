<?php

declare(strict_types=1);

namespace Modules\ApiKey\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\ApiKey\Actions\CreateApiKeyAction;
use Modules\ApiKey\DTOs\ApiKeyDTO;
use Modules\User\Models\User;

test('it can authenticate using a valid api key', function () {
    // Add a temporary route protected by api_key middleware
    Route::get('/api/test-api-key', function () {
        return response()->json(['message' => 'authenticated']);
    })->middleware('api_key');

    $user = User::factory()->create();
    $action = new CreateApiKeyAction;
    $result = $action->execute(new ApiKeyDTO(name: 'Test Key'), $user->id);

    $response = $this->withHeader('X-API-Key', $result['plain_text_key'])
        ->getJson('/api/test-api-key');

    $response->assertStatus(200)
        ->assertJsonPath('message', 'authenticated');
});

test('it fails with an invalid api key', function () {
    Route::get('/api/test-api-key-fail', function () {
        return response()->json(['message' => 'authenticated']);
    })->middleware('api_key');

    $response = $this->withHeader('X-API-Key', 'invalid-key')
        ->getJson('/api/test-api-key-fail');

    $response->assertStatus(401)
        ->assertJsonPath('message', 'Invalid API Key.');
});

test('it fails when IP is not in whitelist', function () {
    Route::get('/api/test-api-key-ip', function () {
        return response()->json(['message' => 'authenticated']);
    })->middleware('api_key');

    $user = User::factory()->create();
    $action = new CreateApiKeyAction;
    $result = $action->execute(new ApiKeyDTO(
        name: 'Test Key',
        ip_whitelist: ['1.2.3.4']
    ), $user->id);

    $response = $this->withHeader('X-API-Key', $result['plain_text_key'])
        ->withServerVariables(['REMOTE_ADDR' => '192.168.1.1'])
        ->getJson('/api/test-api-key-ip');

    $response->assertStatus(403)
        ->assertJsonPath('message', 'IP address not allowed.');
});

test('it succeeds when IP is in whitelist', function () {
    Route::get('/api/test-api-key-ip-success', function () {
        return response()->json(['message' => 'authenticated']);
    })->middleware('api_key');

    $user = User::factory()->create();
    $action = new CreateApiKeyAction;
    $result = $action->execute(new ApiKeyDTO(
        name: 'Test Key',
        ip_whitelist: ['127.0.0.1']
    ), $user->id);

    $response = $this->withHeader('X-API-Key', $result['plain_text_key'])
        ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->getJson('/api/test-api-key-ip-success');

    $response->assertStatus(200);
});
