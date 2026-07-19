<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\IAM\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $token = $this->user->createToken('test-device', ['*']);
    $this->withToken($token->plainTextToken);
});

describe('Devices Filtering', function () {
    it('filters by name using LIKE partial match', function () {
        $this->user->tokens()->create(['name' => 'My iPhone', 'token' => hash('sha256', Str::random(40))]);
        $this->user->tokens()->create(['name' => 'Android Tablet', 'token' => hash('sha256', Str::random(40))]);

        $response = $this->getJson('/api/v1/auth/devices?filter[name]=iPhone');

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('My iPhone');
    })->group('v1', 'filter');
})->group('v1', 'filter');

describe('Devices Search', function () {
    it('searches across name and ip_address', function () {
        $this->user->tokens()->create([
            'name' => 'Office PC',
            'token' => hash('sha256', Str::random(40)),
            'ip_address' => '192.168.1.1',
        ]);
        $this->user->tokens()->create([
            'name' => 'Home Laptop',
            'token' => hash('sha256', Str::random(40)),
            'ip_address' => '10.0.0.1',
        ]);

        $response = $this->getJson('/api/v1/auth/devices?search=192.168');

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('Office PC');
    })->group('v1', 'filter');
})->group('v1', 'filter');

describe('Devices Sorting', function () {
    it('sorts ascending by name', function () {
        $this->user->tokens()->create(['name' => 'Alpha', 'token' => hash('sha256', Str::random(40))]);
        $this->user->tokens()->create(['name' => 'Beta', 'token' => hash('sha256', Str::random(40))]);

        $response = $this->getJson('/api/v1/auth/devices?sort=name');

        $names = collect($response->json('data'))->pluck('name')->all();
        $sorted = collect($names)->sort()->values()->all();
        expect($names)->toBe($sorted);
    })->group('v1', 'filter');

    it('sorts descending with minus prefix', function () {
        $this->user->tokens()->create(['name' => 'Alpha', 'token' => hash('sha256', Str::random(40))]);
        $this->user->tokens()->create(['name' => 'Beta', 'token' => hash('sha256', Str::random(40))]);

        $response = $this->getJson('/api/v1/auth/devices?sort=-name');

        $names = collect($response->json('data'))->pluck('name')->all();
        $sorted = collect($names)->sortDesc()->values()->all();
        expect($names)->toBe($sorted);
    })->group('v1', 'filter');
})->group('v1', 'filter');

describe('Devices Pagination', function () {
    it('paginates results with default per page', function () {
        $response = $this->getJson('/api/v1/auth/devices');

        expect($response->json('meta.per_page'))->toBe(15);
    })->group('v1', 'filter');

    it('respects requested page size', function () {
        for ($i = 0; $i < 5; $i++) {
            $this->user->tokens()->create(['name' => "Device {$i}", 'token' => hash('sha256', Str::random(40))]);
        }

        $response = $this->getJson('/api/v1/auth/devices?page[size]=2');

        expect($response->json('data'))->toHaveCount(2)
            ->and($response->json('meta.per_page'))->toBe(2);
    })->group('v1', 'filter');

    it('rejects page size exceeding 100', function () {
        $response = $this->getJson('/api/v1/auth/devices?page[size]=200');

        $response->assertStatus(422);
    })->group('v1', 'filter');
})->group('v1', 'filter');

describe('Devices Sparse Fields', function () {
    it('selects only requested fields', function () {
        $response = $this->getJson('/api/v1/auth/devices?fields[personal_access_tokens]=id,name');

        expect($response->json('data.0'))->toHaveKeys(['id', 'name', 'is_current']);
    })->group('v1', 'filter');
})->group('v1', 'filter');
