<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureEmailIsVerified;
use Illuminate\Support\Facades\Route;

covers(EnsureEmailIsVerified::class);

describe('EnsureEmailIsVerified', function (): void {

    beforeEach(function (): void {
        Route::middleware(['api', 'auth:sanctum', 'verified'])
            ->get('/__test/verified-only', fn (): array => ['ok' => true]);
    });

    it('returns unauthenticated problem response when no user is authenticated', function (): void {
        $response = $this->getJson('/__test/verified-only');

        assertProblemResponse($response, 401, 'authentication-required');
    });

    it('passes the request through when the user email is verified', function (): void {
        loginAsUser();

        $response = $this->getJson('/__test/verified-only');

        $response->assertOk()->assertJson(['ok' => true]);
    });

    it('returns forbidden problem response when the user email is not verified', function (): void {
        loginAsUnverifiedUser();

        $response = $this->getJson('/__test/verified-only');

        assertProblemResponse($response, 403, 'access-denied');
    });

});
