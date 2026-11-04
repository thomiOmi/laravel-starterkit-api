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
 * Headers that mimic a browser request from a first-party frontend.
 *
 * They must not change how the API authenticates: bearer tokens only.
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

    it('does not authenticate api requests through the web guard', function (): void {
        $user = UserFactory::new()->createOne(['email_verified_at' => now()]);

        $this->actingAs($user, 'web')
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
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

describe('bearer-only cors', function (): void {
    it('does not allow credentialed preflight requests', function (): void {
        config(['cors.allowed_origins' => ['http://localhost:5173']]);

        $response = $this->call('OPTIONS', '/api/v1/auth/login', server: [
            'HTTP_ORIGIN' => 'http://localhost:5173',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $response->assertNoContent();
        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
        $response->assertHeaderMissing('Access-Control-Allow-Credentials');
    });

    it('does not apply cors to the sanctum csrf cookie path', function (): void {
        expect(config()->array('cors.paths'))->not->toContain('sanctum/csrf-cookie');
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
