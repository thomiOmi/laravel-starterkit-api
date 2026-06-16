<?php

declare(strict_types=1);

use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helpers\WithAdminUser;

uses(WithAdminUser::class);

beforeEach(function () {
    $this->setUpAdminUser();
});

describe('User CRUD Operations V1', function () {
    it('denies access to unauthorized users', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/users')
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('rejects invalid search parameter type', function () {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/users?search[]=invalid')
            ->assertBadRequest()
            ->assertJsonStructure([
                'type',
                'title',
                'status',
                'message',
                'detail',
            ]);
    });

    it('creates a new user', function () {
        $payload = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->adminPost('/api/v1/users', $payload)
            ->assertCreated()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'name', 'email'],
            ])
            ->assertJsonPath('data.name', 'Jane Doe')
            ->assertJsonPath('data.email', 'jane@example.com');
    });

    it('shows a user', function () {
        $user = User::factory()->create();

        $this->adminGet("/api/v1/users/{$user->id}")
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.email', $user->email);
    });

    it('returns 404 for non-existent user', function () {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/users/non-existent-id')
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
});
