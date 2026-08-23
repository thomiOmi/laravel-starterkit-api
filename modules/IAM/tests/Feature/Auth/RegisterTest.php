<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\RegisterController;
use Modules\IAM\Models\Role;

covers(RegisterController::class);

describe('POST /api/v1/auth/register', function () {
    beforeEach(function () {
        config()->set('iam.features.self-registration', true);
        Role::firstOrCreate(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
    });

    it('creates a user with the User role and returns a token', function () {
        Event::fake([Registered::class]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'iPhone',
        ]);

        assertSuccessResponse($response, 201);
        expect($response->json('data.user.email'))->toBe('jane@example.com')
            ->and($response->json('data.user.roles'))->toContain(RoleEnum::User->value)
            ->and($response->json('data.access_token'))->not->toBeEmpty();

        Event::assertDispatched(Registered::class);
    });

    it('rejects duplicate emails', function () {
        UserFactory::new()->createOne(['email' => 'jane@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Clone',
            'email' => 'JANE@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['email']);
    });

    it('is disabled when the feature flag is off', function () {
        config()->set('iam.features.self-registration', false);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        assertProblemResponse($response, 403);
    });
});
