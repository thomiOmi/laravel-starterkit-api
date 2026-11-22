<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\RegisterController;
use Modules\IAM\Models\Role;

covers(RegisterController::class);

describe('POST /api/v1/auth/register', function (): void {
    beforeEach(function (): void {
        config()->set('iam.features.self-registration', true);
        Role::query()->firstOrCreate(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
    });

    it('creates a user with the User role and returns a token', function (): void {
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

    it('rejects duplicate emails', function (): void {
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

    it('rejects mixed-script homograph emails', function (): void {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => "\u{0430}dmin@example.com",
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['email']);
    });

    it('rejects a Unicode case variant of an existing email', function (): void {
        $first = $this->postJson('/api/v1/auth/register', [
            'name' => 'Emile',
            'email' => "\u{00C9}mile@example.com",
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        assertSuccessResponse($first, 201);

        $second = $this->postJson('/api/v1/auth/register', [
            'name' => 'Emile Clone',
            'email' => "\u{00E9}mile@example.com",
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        assertProblemResponse($second, 422, 'validation');
        $second->assertJsonValidationErrors(['email']);
    });

    it('does not run the unique query when the email fails a cheap rule', function (string $email): void {
        $existsQueries = 0;

        DB::listen(function ($query) use (&$existsQueries): void {
            $sql = strtolower($query->sql);

            if (str_contains($sql, 'count(') && preg_match('/from [`"]users[`"]/', $sql) === 1) {
                $existsQueries++;
            }
        });

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['email']);
        expect($existsQueries)->toBe(0);
    })->with([
        'malformed' => 'not-an-email',
        'too long' => str_repeat('a', 250).'@example.com',
    ]);

    it('reports only the length error for an overlong malformed email', function (): void {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => str_repeat('a', 300),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        assertProblemResponse($response, 422, 'validation');
        expect($response->json('errors.email'))
            ->toHaveCount(1)
            ->and($response->json('errors.email.0'))->toContain('255');
    });

    it('runs the unique query for a well-formed email', function (): void {
        $existsQueries = 0;

        DB::listen(function ($query) use (&$existsQueries): void {
            $sql = strtolower($query->sql);

            if (str_contains($sql, 'count(') && preg_match('/from [`"]users[`"]/', $sql) === 1) {
                $existsQueries++;
            }
        });

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        assertSuccessResponse($response, 201);
        expect($existsQueries)->toBe(1);
    });

    it('skips the compromised password check when the confirmation does not match', function (): void {
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);
        app()->detectEnvironment(fn (): string => 'production');

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);

        assertProblemResponse($response, 422, 'validation');
        expect($response->json('errors.password'))
            ->toHaveCount(1)
            ->and($response->json('errors.password.0'))->toContain('confirmation');
        Http::assertNothingSent();
    });

    it('runs the compromised password check when the confirmation matches', function (): void {
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);
        app()->detectEnvironment(fn (): string => 'production');

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Str0ng-Passw0rd!9',
            'password_confirmation' => 'Str0ng-Passw0rd!9',
        ]);

        assertSuccessResponse($response, 201);
        Http::assertSentCount(1);
    });

    it('is disabled when the feature flag is off', function (): void {
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
