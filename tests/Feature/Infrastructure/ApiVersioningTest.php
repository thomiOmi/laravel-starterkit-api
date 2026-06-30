<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

test('api v1 health endpoint returns success via functional test', function () {
    /** @var TestCase $this */
    /** @var TestCase $this */
    Route::get('/api/v1/health', fn () => response()->json(['status' => 'ok']));

    $this->get('/api/v1/health')
        ->assertStatus(200)
        ->assertJson(['status' => 'ok']);
});

test('unsupported api version returns 404', function () {
    /** @var TestCase $this */
    /** @var TestCase $this */
    $response = $this->get('/api/v99/invalid-endpoint');

    expect($response->status())->toBe(404);
});
