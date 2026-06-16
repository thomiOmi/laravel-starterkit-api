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
});
