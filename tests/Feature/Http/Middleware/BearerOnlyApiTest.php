<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Factory;
use Illuminate\Routing\Router;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\LoginController;
use Modules\IAM\Http\Controllers\V1\MeController;

covers([MeController::class, LoginController::class]);

/**
 * Headers that make Sanctum treat the request as coming from a stateful
 * first-party frontend (http://localhost:3000 is in config('sanctum.stateful')).
 *
 * @return array<string, string>
 */
function statefulOriginHeaders(): array
{
    return [
        'Origin' => 'http://localhost:3000',
        'Referer' => 'http://localhost:3000/',
    ];
}

describe('bearer-only api', function (): void {
    it('does not register sanctum stateful SPA middleware on the api group', function (): void {
        $apiMiddleware = resolve(Router::class)->getMiddlewareGroups()['api'];

        expect($apiMiddleware)->not->toContain(EnsureFrontendRequestsAreStateful::class);
    });

    it('rejects unauthenticated requests from stateful origins', function (): void {
        $response = $this->withHeaders(statefulOriginHeaders())
            ->getJson('/api/v1/auth/me');

        assertProblemResponse($response, 401);
    });

    it('accepts real bearer tokens from stateful origins', function (): void {
        $user = UserFactory::new()->createOne();
        $plainTextToken = $user->createToken('bearer-only')->plainTextToken;

        $this->withHeaders(statefulOriginHeaders())
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();

        resolve(Factory::class)->forgetGuards();

        $response = $this->withHeaders(statefulOriginHeaders())
            ->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/v1/auth/me');

        assertSuccessResponse($response, 200);
        expect($response->json('data.email'))->toBe($user->email);
    });

    it('logs in from stateful origins without a CSRF cookie', function (): void {
        UserFactory::new()->createOne([
            'email' => 'bearer-only@example.com',
            'password' => 'password123',
        ]);

        $response = $this->withHeaders(statefulOriginHeaders())
            ->postJson('/api/v1/auth/login', [
                'email' => 'bearer-only@example.com',
                'password' => 'password123',
                'device_name' => 'external-client',
            ]);

        assertSuccessResponse($response, 200);
        expect($response->json('data.access_token'))->not->toBeEmpty()
            ->and($response->json('data.token_type'))->toBe('Bearer')
            ->and($response->headers->getCookies())->toBeEmpty();
    });
});

describe('sanctum csrf cookie route', function (): void {
    it('does not expose the sanctum csrf cookie endpoint', function (): void {
        $response = $this->get('/sanctum/csrf-cookie');

        $response->assertNotFound();

        expect($response->headers->getCookies())->toBeEmpty();
    });

    it('does not register the sanctum csrf cookie named route', function (): void {
        expect(resolve(Router::class)->has('sanctum.csrf-cookie'))->toBeFalse();
    });
});
