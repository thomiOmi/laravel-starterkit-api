<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\Sanctum\PersonalAccessToken;
use App\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\IAM\Models\User;

describe('AUTH-01 register', function (): void {
    beforeEach(function (): void {
        $this->seed(IAMSeeder::class);

        config()->set('rate-limiting.auth.limit_per_email', 100);
        config()->set('rate-limiting.auth.limit_per_ip', 100);
    });

    it('creates a user with the User role and issues a token', function (): void {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        assertSuccessResponse($response, 201, 'Created');

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        expect($user->hasRole(RoleEnum::User->value))->toBeTrue();
        expect($user->email_verified_at)->toBeNull();
        expect($response->json('data.access_token'))->toBeString();
    })->group('module:iam', 'smoke');

    it('does not auto-verify the email', function (): void {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertCreated();

        expect(User::where('email', 'jane@example.com')->firstOrFail()->status)->toBe(UserStatusEnum::Pending);
    })->group('module:iam');
});

describe('AUTH-02 login', function (): void {
    beforeEach(function (): void {
        config()->set('rate-limiting.auth.limit_per_email', 100);
        config()->set('rate-limiting.auth.limit_per_ip', 100);
    });

    it('issues a Sanctum bearer token for valid credentials', function (): void {
        UserFactory::new()->createOne([
            'email' => 'jane@example.com',
            'password' => 'secret-password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'secret-password',
        ]);

        assertSuccessResponse($response, 200, 'OK');

        expect($response->json('data'))
            ->toHaveKeys(['user', 'access_token', 'token_type', 'expires_at', 'expires_in']);
        expect($response->json('data.token_type'))->toBe('Bearer');
    })->group('module:iam', 'smoke');

    it('rejects invalid credentials with a generic problem response', function (): void {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ]);

        assertProblemResponse($response, 422, 'validation');
        expect($response->json('errors.email.0'))->toBe(__('auth.failed'));
    })->group('module:iam');
});

describe('AUTH-03 logout', function (): void {
    beforeEach(function (): void {
        $this->seed(IAMSeeder::class);
    });

    it('revokes the current token so /me returns 401', function (): void {
        $user = UserFactory::new()->createOne([
            'email' => 'jane@example.com',
            'password' => 'secret-password',
        ]);
        $token = $user->createToken('test-device', ['*']);

        $response = $this->withToken($token->plainTextToken)->postJson('/api/v1/auth/logout');

        assertSuccessResponse($response, 204);

        expect(PersonalAccessToken::query()->whereKey($token->accessToken->getKey())->exists())->toBeFalse();

        $this->app->forgetInstance('auth');
        auth()->forgetGuards();

        $this->withToken($token->plainTextToken)->getJson('/api/v1/auth/me')->assertUnauthorized();
    })->group('module:iam', 'smoke');

    it('allows unverified users to revoke their session via logout', function (): void {
        $user = UserFactory::new()->unverified()->createOne([
            'email' => 'unverified-logout@example.com',
            'password' => 'secret-password',
        ]);
        $token = $user->createToken('test-device', ['*']);

        $response = $this->withToken($token->plainTextToken)->postJson('/api/v1/auth/logout');

        assertSuccessResponse($response, 204);

        expect(PersonalAccessToken::query()->whereKey($token->accessToken->getKey())->exists())->toBeFalse();
    })->group('module:iam');
});

describe('AUTH-04 device management', function (): void {
    beforeEach(function (): void {
        $this->seed(IAMSeeder::class);
    });

    it('stores the device_name as the token name on login', function (): void {
        UserFactory::new()->createOne([
            'email' => 'jane@example.com',
            'password' => 'secret-password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'device_name' => 'My iPhone',
        ])->assertOk();

        $token = PersonalAccessToken::query()
            ->where('name', 'My iPhone')
            ->firstOrFail();

        expect($token->name)->toBe('My iPhone');
    })->group('module:iam', 'smoke');

    it('falls back to the user agent as the token name when device_name is omitted', function (): void {
        UserFactory::new()->createOne([
            'email' => 'jane@example.com',
            'password' => 'secret-password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'secret-password',
        ], ['User-Agent' => 'TestAgent/1.0'])->assertOk();

        expect(PersonalAccessToken::query()->where('name', 'TestAgent/1.0')->exists())->toBeTrue();
    })->group('module:iam');

    it('lists devices and revokes a specific device', function (): void {
        $user = UserFactory::new()->createOne([
            'email' => 'jane@example.com',
            'password' => 'secret-password',
        ]);
        $tokenA = $user->createToken('device-a', ['*']);
        $tokenB = $user->createToken('device-b', ['*']);

        $list = $this->withToken($tokenA->plainTextToken)->getJson('/api/v1/auth/devices');

        assertSuccessResponse($list, 200, 'OK');
        $list->assertJsonFragment(['name' => 'device-a']);
        $list->assertJsonFragment(['name' => 'device-b']);

        $delete = $this->withToken($tokenA->plainTextToken)
            ->deleteJson(route('v1.iam.auth.devices.delete', ['device' => $tokenB->accessToken]));

        assertSuccessResponse($delete, 200);
        expect(PersonalAccessToken::query()->whereKey($tokenB->accessToken->getKey())->exists())->toBeFalse();
    })->group('module:iam');

    it('revokes all other device tokens via logout-others', function (): void {
        $user = UserFactory::new()->createOne([
            'email' => 'jane@example.com',
            'password' => 'secret-password',
        ]);
        $tokenA = $user->createToken('device-a', ['*']);
        $tokenB = $user->createToken('device-b', ['*']);

        $this->withToken($tokenA->plainTextToken)->postJson('/api/v1/auth/devices/logout-others', [
            'current_password' => 'secret-password',
        ])->assertOk();

        expect($user->tokens()->where('id', $tokenB->accessToken->getKey())->exists())->toBeFalse();
        expect($user->tokens()->where('id', $tokenA->accessToken->getKey())->exists())->toBeTrue();
    })->group('module:iam');
});

describe('AUTH-05 email verification', function (): void {
    it('marks the email verified via the signed verification URL', function (): void {
        $user = UserFactory::new()->unverified()->createOne();

        $url = URL::temporarySignedRoute(
            'v1.iam.auth.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $response = $this->withToken($user->createToken('verify')->plainTextToken)->getJson($url);

        assertSuccessResponse($response, 200, 'OK');
        expect($response->json('data.verified'))->toBeTrue();

        $user->refresh();

        expect($user->email_verified_at)->not->toBeNull();
        expect($user->status)->toBe(UserStatusEnum::Active);
    })->group('module:iam', 'smoke');

    it('rejects an invalid verification signature', function (): void {
        $user = UserFactory::new()->unverified()->createOne();

        $response = $this->withToken($user->createToken('verify')->plainTextToken)
            ->getJson("/api/v1/auth/email/verify/{$user->id}/invalid-hash");

        assertProblemResponse($response, 403, 'access-denied');
    })->group('module:iam');

    it('sends a verification notification on registration', function (): void {
        Notification::fake();

        $this->seed(IAMSeeder::class);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertCreated();

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    })->group('module:iam');
});

describe('AUTH-06 password reset', function (): void {
    beforeEach(function (): void {
        config()->set('rate-limiting.auth.limit_per_email', 100);
        config()->set('rate-limiting.auth.limit_per_ip', 100);
    });

    it('sends a reset link for a registered email', function (): void {
        Mail::fake();

        UserFactory::new()->createOne(['email' => 'jane@example.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'jane@example.com',
        ]);

        assertSuccessResponse($response, 200);
        expect($response->json('detail'))->toBe(__('auth.password_reset_link_sent'));
    })->group('module:iam', 'smoke');

    it('resets the password via the emailed token and logs in with the new password', function (): void {
        $user = UserFactory::new()->createOne([
            'email' => 'jane@example.com',
            'password' => 'old-password',
        ]);
        $token = app('auth.password.broker')->createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'jane@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        assertSuccessResponse($response, 200);
        expect($response->json('detail'))->toBe(__('auth.password_reset_success'));

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'new-password',
        ]);

        assertSuccessResponse($login, 200, 'OK');
    })->group('module:iam', 'smoke');

    it('returns the same response for an unknown email to prevent enumeration', function (): void {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nobody@example.com',
        ]);

        assertSuccessResponse($response, 200);
        expect($response->json('detail'))->toBe(__('auth.password_reset_link_sent'));
    })->group('module:iam');
});
