<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Modules\IAM\Models\User;
use Symfony\Component\HttpFoundation\Response;

describe('EnsureEmailIsVerified', function () {

    it('returns unauthenticated ProblemResponse when no user', function () {
        $response = (new EnsureEmailIsVerified)->handle(
            new Request,
            fn ($req): Response => new Response('OK'),
        );

        expect($response->getStatusCode())->toBe(401);
        expect($response->getContent())->toContain('Unauthenticated');
    });

    it('returns forbidden ProblemResponse when email is not verified', function () {
        $user = User::factory()->unverified()->create();
        $request = new Request;
        $request->setUserResolver(fn () => $user);

        $response = (new EnsureEmailIsVerified)->handle(
            $request,
            fn ($req): Response => new Response('OK'),
        );

        expect($response->getStatusCode())->toBe(403);
        expect($response->getContent())->toContain('Email Not Verified');
    });

    it('passes request through when user email is verified', function () {
        $user = User::factory()->create();
        $user->markEmailAsVerified();
        $request = new Request;
        $request->setUserResolver(fn () => $user);

        $response = (new EnsureEmailIsVerified)->handle(
            $request,
            fn ($req): Response => new Response('OK'),
        );

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('OK');
    });

});
