<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureEmailIsVerified;

covers(EnsureEmailIsVerified::class);

use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

describe('EnsureEmailIsVerified', function () {

    it('returns unauthenticated ProblemResponse when no user', function () {
        $response = TestResponse::fromBaseResponse(
            (new EnsureEmailIsVerified)->handle(
                new Request,
                fn ($req): Response => new Response('OK'),
            )
        );

        assertProblemResponse($response, 401, 'authentication-required');
    });

    describe('with a verified user', function () {

        beforeEach(function () {
            $this->user = loginAsUser();
        });

        it('passes request through when user email is verified', function () {
            $request = new Request;
            $request->setUserResolver(fn () => $this->user);

            $response = TestResponse::fromBaseResponse(
                (new EnsureEmailIsVerified)->handle(
                    $request,
                    fn ($req): Response => new Response('OK'),
                )
            );

            expect($response->getStatusCode())->toBe(200)
                ->and($response->getContent())->toBe('OK');
        });

    });

    describe('with an unverified user', function () {

        beforeEach(function () {
            $this->user = loginAsUnverifiedUser();
        });

        it('returns forbidden ProblemResponse when email is not verified', function () {
            $request = new Request;
            $request->setUserResolver(fn () => $this->user);

            $response = TestResponse::fromBaseResponse(
                (new EnsureEmailIsVerified)->handle(
                    $request,
                    fn ($req): Response => new Response('OK'),
                )
            );

            assertProblemResponse($response, 403, 'access-denied');
        });

    });

});
