<?php

declare(strict_types=1);

namespace Tests\Feature;

test('it sets the locale based on Accept-Language header to id', function () {
    $response = $this->withHeader('Accept-Language', 'id')
        ->getJson('/api/V1/auth/me');

    // Should return 401 Unauthenticated but in Indonesian
    $response->assertStatus(401)
        ->assertJsonPath('message', 'Tidak terautentikasi');
});

test('it sets the locale based on Accept-Language header to en', function () {
    $response = $this->withHeader('Accept-Language', 'en')
        ->getJson('/api/V1/auth/me');

    // Should return 401 Unauthenticated in English
    $response->assertStatus(401)
        ->assertJsonPath('message', 'Unauthenticated');
});

test('it falls back to default locale if header is missing', function () {
    $response = $this->getJson('/api/V1/auth/me');

    // Default is usually 'en'
    $response->assertStatus(401)
        ->assertJsonPath('message', 'Unauthenticated');
});

test('it falls back to default locale if header is unsupported', function () {
    $response = $this->withHeader('Accept-Language', 'fr')
        ->getJson('/api/V1/auth/me');

    $response->assertStatus(401)
        ->assertJsonPath('message', 'Unauthenticated');
});
