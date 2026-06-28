<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

test('ForceJsonResponse ensures JSON accept header', function () {
    Route::get('/api/v1/_test/json-accept', function () {
        return response()->json(['message' => 'ok']);
    })->middleware('api');

    $response = $this->get('/api/v1/_test/json-accept', [
        'Accept' => 'text/html',
    ]);

    $response->assertSuccessful()
        ->assertJson(['message' => 'ok']);
});

test('TraceIdMiddleware adds X-Trace-ID header', function () {
    Route::get('/api/v1/_test/trace-id', function () {
        return response()->json(['message' => 'ok']);
    })->middleware('api');

    $response = $this->getJson('/api/v1/_test/trace-id');

    $response->assertSuccessful()
        ->assertHeader('X-Trace-ID');
});

test('Sunset middleware sets Sunset header', function () {
    Route::get('/api/v1/_test/sunset', function () {
        return response()->json(['message' => 'ok']);
    })->middleware('sunset:2026-12-31');

    $response = $this->getJson('/api/v1/_test/sunset');

    $response->assertSuccessful()
        ->assertHeader('Sunset');
});

test('Sunset middleware formats date as RFC7231', function () {
    Route::get('/api/v1/_test/sunset-format', function () {
        return response()->json(['message' => 'ok']);
    })->middleware('sunset:2026-12-31');

    $response = $this->getJson('/api/v1/_test/sunset-format');

    $response->assertHeader('Sunset');
    $sunset = $response->headers->get('Sunset');
    expect($sunset)->toMatch('/^[A-Z][a-z]{2}, \d{2} [A-Z][a-z]{2} \d{4} /');
});

test('SetLocaleMiddleware respects Accept-Language header', function () {
    $response = $this->withHeader('Accept-Language', 'id')
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(Response::HTTP_UNAUTHORIZED)
        ->assertJsonPath('title', 'Tidak terautentikasi');
});

test('SetLocaleMiddleware falls back to default locale', function () {
    $response = $this->withHeader('Accept-Language', 'fr')
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(Response::HTTP_UNAUTHORIZED)
        ->assertJsonPath('title', 'Unauthenticated');
});

test('ForceJsonResponse returns JSON for non-API routes when accessed via API prefix', function () {
    Route::get('/api/v1/_test/api-only', function () {
        return response()->json(['api' => true]);
    })->middleware('api');

    $this->getJson('/api/v1/_test/api-only')
        ->assertSuccessful()
        ->assertJson(['api' => true]);
});
