<?php

declare(strict_types=1);

namespace Tests\Feature;

test('it sets the locale based on Accept-Language header to id', function () {
    $response = $this->withHeader('Accept-Language', 'id')
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(401)
        ->assertJsonPath('title', 'Tidak terautentikasi');
});

test('it sets the locale based on Accept-Language header to en', function () {
    $response = $this->withHeader('Accept-Language', 'en')
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(401)
        ->assertJsonPath('title', 'Unauthenticated');
});

test('it falls back to default locale if header is missing', function () {
    $response = $this->getJson('/api/v1/auth/me');

    $response->assertStatus(401)
        ->assertJsonPath('title', 'Unauthenticated');
});

test('it falls back to default locale if header is unsupported', function () {
    $response = $this->withHeader('Accept-Language', 'fr')
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(401)
        ->assertJsonPath('title', 'Unauthenticated');
});
