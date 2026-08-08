<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Database\Seeders\IAMSeeder;

describe('user status management', function (): void {
    beforeEach(function (): void {
        $this->seed(IAMSeeder::class);
    });

    it('allows an admin to update a user status', function (): void {
        loginAsAdmin();

        $target = UserFactory::new()->createOne();

        $response = $this->putJson("/api/v1/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'status' => 'banned',
        ]);

        assertSuccessResponse($response, 200, 'OK');
        expect($response->json('data.status'))->toBe('banned');
        expect($response->json('data.status_label'))->toBe('Banned');
        expect($target->refresh()->status->value)->toBe('banned');
    })->group('module:iam');

    it('rejects an invalid status value', function (): void {
        loginAsAdmin();

        $target = UserFactory::new()->createOne();

        $response = $this->putJson("/api/v1/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'status' => 'not-a-status',
        ]);

        assertProblemResponse($response, 422, 'validation');
        expect($response->json('errors.status'))->not->toBeNull();
    })->group('module:iam');

    it('prohibits users without edit permission from changing the status', function (): void {
        $user = loginAsRole(RoleEnum::User);

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'banned',
        ]);

        assertProblemResponse($response, 422, 'validation');
        expect($response->json('errors.status'))->not->toBeNull();
        expect($user->refresh()->status->value)->toBe('active');
    })->group('module:iam');

    it('exposes the status in the user resource', function (): void {
        loginAsAdmin();

        $target = UserFactory::new()->suspended()->createOne();

        $response = $this->getJson("/api/v1/users/{$target->id}");

        assertSuccessResponse($response, 200, 'OK');
        expect($response->json('data.status'))->toBe('suspended');
    })->group('module:iam');
});
