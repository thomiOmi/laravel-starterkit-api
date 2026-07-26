<?php

declare(strict_types=1);

use App\Http\Middleware\FeatureFlagMiddleware;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

describe('FeatureFlagMiddleware', function () {

    it('passes request through when feature is active', function () {
        Feature::define('beta-feature', true);

        $response = (new FeatureFlagMiddleware)->handle(
            new Request,
            fn ($req): Response => new Response('OK'),
            'beta-feature',
        );

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('OK');
    });

    it('throws AccessDeniedHttpException when feature is inactive', function () {
        Feature::define('beta-feature', false);

        (new FeatureFlagMiddleware)->handle(
            new Request,
            fn ($req): Response => new Response('OK'),
            'beta-feature',
        );
    })->throws(AccessDeniedHttpException::class, 'Forbidden');

});
