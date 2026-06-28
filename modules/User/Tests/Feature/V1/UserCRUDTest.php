<?php

declare(strict_types=1);

use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helpers\WithAdminUser;

uses(WithAdminUser::class);

beforeEach(function () {
    $this->setUpAdminUser();
});

describe('User CRUD Operations V1', function () {
    it('lists paginated users', function () {
        User::factory()->count(3)->create();

        $this->adminGet('/api/v1/users')
            ->assertJson(fn (AssertableJson $json) => $json->has('data')
                ->has('meta', fn (AssertableJson $meta) => $meta->whereType('current_page', 'integer')
                    ->whereType('last_page', 'integer')
                    ->whereType('per_page', 'integer')
                    ->where('total', fn (int $total) => $total >= 4)
                    ->etc()
                )
                ->etc()
            );
    });

    it('paginates users with custom page size', function () {
        User::factory()->count(5)->create();

        $this->adminGet('/api/v1/users?page[size]=3')
            ->assertJsonPath('meta.per_page', 3);
    });

    it('denies access to unauthorized users', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/users')
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('rejects invalid search parameter type', function () {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/users?search[]=invalid')
            ->assertBadRequest()
            ->assertJsonStructure([
                'type',
                'title',
                'status',
                'detail',
            ]);
    });

    it('creates a new user', function () {
        $password = config('auth.default_password');
        $payload = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ];
        $this->adminPost('/api/v1/users', $payload)
            ->assertCreated()
            ->assertJson(fn (AssertableJson $json) => $json->whereType('status', 'integer')
                ->where('data.name', 'Jane Doe')
                ->where('data.email', 'jane@example.com')
                ->whereType('data.id', 'string')
                ->etc()
            );
    });

    it('shows a user', function () {
        $user = User::factory()->create();

        $this->adminGet("/api/v1/users/{$user->id}")
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.email', $user->email);
    });

    it('returns 404 for non-existent user', function () {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/users/non-existent-id')
            ->assertNotFound();
    });

    it('updates a user', function () {
        $user = User::factory()->create();
        $payload = [
            'name' => 'Updated Name',
            'email' => $user->email,
        ];

        $this->adminPut("/api/v1/users/{$user->id}", $payload)
            ->assertSuccessful()
            ->assertJsonPath('data.name', 'Updated Name');
    });

    it('deletes a user', function () {
        $user = User::factory()->create();

        $this->adminDelete("/api/v1/users/{$user->id}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted($user);
    });

    it('prevents deleting own account', function () {
        $this->adminDelete('/api/v1/users/'.$this->admin->id)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('assigns roles to a user', function () {
        $user = User::factory()->create();

        $this->adminPut("/api/v1/users/{$user->id}/roles", [
            'roles' => ['admin', 'user'],
        ])
            ->assertSuccessful()
            ->assertJsonPath('data.id', $user->id);
    });

    it('assigning roles returns user with roles loaded', function () {
        $user = User::factory()->create();

        $this->adminPut("/api/v1/users/{$user->id}/roles", [
            'roles' => ['user'],
        ])
            ->assertSuccessful()
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'roles']]);
    });

    it('denies assigning roles to unauthorized users', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($otherUser);

        $this->putJson("/api/v1/users/{$user->id}/roles", [
            'roles' => ['admin'],
        ])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('returns 404 when assigning roles to non-existent user', function () {
        Sanctum::actingAs($this->admin);

        $this->putJson('/api/v1/users/non-existent-id/roles', [
            'roles' => ['admin'],
        ])
            ->assertNotFound();
    });

    it('validates roles exist when assigning', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($this->admin);

        $this->putJson("/api/v1/users/{$user->id}/roles", [
            'roles' => ['non-existent-role'],
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('requires at least one role when assigning', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($this->admin);

        $this->putJson("/api/v1/users/{$user->id}/roles", [
            'roles' => [],
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });
});
