<?php

declare(strict_types=1);

use App\Http\Middleware\FeatureFlagMiddleware;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

covers(FeatureFlagMiddleware::class);

describe('FeatureFlagMiddleware', function (): void {
    beforeEach(function (): void {
        Feature::flushCache();
    });

    it('passes request through when feature is active', function (): void {
        Feature::define('beta-feature', true);

        $response = (new FeatureFlagMiddleware)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            'beta-feature',
        );

        expect($response->getStatusCode())->toBe(Response::HTTP_OK)
            ->and($response->getContent())->toBe('OK');
    });

    it('throws AccessDeniedHttpException when feature is inactive', function (): void {
        Feature::define('beta-feature', false);

        (new FeatureFlagMiddleware)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            'beta-feature',
        );
    })->throws(AccessDeniedHttpException::class, 'Forbidden');

    it('passes request through for the guest scope when feature is active', function (): void {
        Feature::define('beta-feature', true);

        $response = (new FeatureFlagMiddleware)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            'beta-feature',
        );

        expect($response->getStatusCode())->toBe(Response::HTTP_OK);
    });

    it('throws AccessDeniedHttpException when feature is deactivated for the guest scope', function (): void {
        Feature::define('beta-feature', true);
        Feature::deactivate('beta-feature');

        (new FeatureFlagMiddleware)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            'beta-feature',
        );
    })->throws(AccessDeniedHttpException::class, 'Forbidden');
});
