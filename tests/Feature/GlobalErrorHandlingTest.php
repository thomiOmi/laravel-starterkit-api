<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

test('system returns 404 for non-existent routes', function () {
    $response = $this->getJson('/api/non-existent-route');

    $response->assertStatus(404);
});

test('system handles validation errors consistently', function () {
    // We use a known route with validation
    $response = $this->postJson('/api/auth/register', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'status',
            'message',
            'errors',
        ])
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('message', 'Validation Failed');
});

test('system handles unauthenticated access', function () {
    $response = $this->getJson('/api/auth/me');

    $response->assertStatus(401);
});

test('system handles unauthorized access', function () {
    // We need a route that requires a specific role/permission
    // Let's assume there is a route that requires 'admin' role
    Route::get('/api/test-admin', function () {
        return response()->json(['message' => 'admin only']);
    })->middleware(['auth:sanctum', 'role:admin']);

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->getJson('/api/test-admin');

    $response->assertStatus(403);
});

test('system handles server errors', function () {
    Route::get('/api/test-error', function () {
        throw new Exception('Test Server Error');
    });

    $response = $this->getJson('/api/test-error');

    $response->assertStatus(500);
});
