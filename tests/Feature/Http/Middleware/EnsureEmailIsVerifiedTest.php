<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureEmailIsVerified;

covers(EnsureEmailIsVerified::class);

use Illuminate\Http\Request;
use Modules\IAM\Database\Factories\UserFactory;
use Symfony\Component\HttpFoundation\Response;

describe('EnsureEmailIsVerified', function () {

    it('returns unauthenticated ProblemResponse when no user', function () {
        $response = (new EnsureEmailIsVerified)->handle(
            new Request,
            fn ($req): Response => new Response('OK'),
        );

        expect($response->getStatusCode())->toBe(401)
            ->and($response->getContent())->toContain('Unauthenticated');
    });

    it('returns forbidden ProblemResponse when email is not verified', function () {
        $user = UserFactory::new()->unverified()->createOne();
        $request = new Request;
        $request->setUserResolver(fn () => $user);

        $response = (new EnsureEmailIsVerified)->handle(
            $request,
            fn ($req): Response => new Response('OK'),
        );

        expect($response->getStatusCode())->toBe(403)
            ->and($response->getContent())->toContain('Email Not Verified');
    });

    it('passes request through when user email is verified', function () {
        $user = UserFactory::new()->createOne();
        $user->markEmailAsVerified();
        $request = new Request;
        $request->setUserResolver(fn () => $user);

        $response = (new EnsureEmailIsVerified)->handle(
            $request,
            fn ($req): Response => new Response('OK'),
        );

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getContent())->toBe('OK');
    });

});
