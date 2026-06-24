<?php

declare(strict_types=1);

use Modules\Role\Models\Role;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helpers\WithAdminUser;

uses(WithAdminUser::class);

beforeEach(function () {
    $this->setUpAdminUser();
    Role::create(['name' => 'editor', 'guard_name' => 'web']);
});

describe('User Role Assignment V1', function () {
    it('allows admin to assign roles to a user', function () {
        $user = User::factory()->create();

        $this->adminPut("/api/v1/users/{$user->id}/roles", [
            'roles' => ['editor'],
        ])
            ->assertSuccessful()
            ->assertJsonPath('data.roles.0.name', 'editor');

        expect($user->fresh()->hasRole('editor'))->toBeTrue();
    });

    it('denies access to users without user.edit permission', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->putJson("/api/v1/users/{$otherUser->id}/roles", [
                'roles' => ['editor'],
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('returns 404 for non-existent user', function () {
        $this->adminPut('/api/v1/users/non-existent-id/roles', [
            'roles' => ['editor'],
        ])
            ->assertNotFound();
    });

    it('validates that roles must exist', function () {
        $user = User::factory()->create();

        $this->adminPut("/api/v1/users/{$user->id}/roles", [
            'roles' => ['non-existent-role'],
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['roles.0']);
    });
});
