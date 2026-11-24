<?php

declare(strict_types=1);

use App\Features\BetaFeature;
use App\Http\Middleware\EnsureFeatureIsActive;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

covers(EnsureFeatureIsActive::class);

describe('EnsureFeatureIsActive', function (): void {
    beforeEach(function (): void {
        Feature::flushCache();
    });

    it('passes request through when feature is active', function (): void {
        Feature::define(BetaFeature::class, true);

        $response = (new EnsureFeatureIsActive)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            BetaFeature::class,
        );

        expect($response->getStatusCode())->toBe(Response::HTTP_OK)
            ->and($response->getContent())->toBe('OK');
    });

    it('throws AccessDeniedHttpException when feature is inactive', function (): void {
        Feature::define(BetaFeature::class, false);

        (new EnsureFeatureIsActive)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            BetaFeature::class,
        );
    })->throws(AccessDeniedHttpException::class);

    it('passes request through for the guest scope when feature is active', function (): void {
        Feature::define(BetaFeature::class, true);

        $response = (new EnsureFeatureIsActive)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            BetaFeature::class,
        );

        expect($response->getStatusCode())->toBe(Response::HTTP_OK);
    });

    it('throws AccessDeniedHttpException when feature is deactivated for the guest scope', function (): void {
        Feature::define(BetaFeature::class, true);
        Feature::deactivate(BetaFeature::class);

        (new EnsureFeatureIsActive)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            BetaFeature::class,
        );
    })->throws(AccessDeniedHttpException::class);

    it('resolves a build-time registry feature before falling back to Pennant', function (): void {
        config()->set('iam.features.self-registration', false);

        expect(fn (): Response => (new EnsureFeatureIsActive)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            'iam.self-registration',
        ))->toThrow(AccessDeniedHttpException::class);
    });

    it('lets an enabled build-time registry feature pass through', function (): void {
        config()->set('iam.features.self-registration', true);

        $response = (new EnsureFeatureIsActive)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            'iam.self-registration',
        );

        expect($response->getStatusCode())->toBe(Response::HTTP_OK);
    });

    it('falls back to Pennant when the registry does not define the feature', function (): void {
        Feature::define('media.external-upload', true);

        $response = (new EnsureFeatureIsActive)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            'media.external-upload',
        );

        expect($response->getStatusCode())->toBe(Response::HTTP_OK);
    });

    it('denies an unregistered two-segment feature that Pennant resolves to false', function (): void {
        Feature::define('audit.runtime-denied', false);

        expect(fn (): Response => (new EnsureFeatureIsActive)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            'audit.runtime-denied',
        ))->toThrow(AccessDeniedHttpException::class);
    });

    it('denies feature names that were never defined', function (): void {
        foreach (['audit.never-registered', 'neverRegisteredFlag'] as $feature) {
            expect(fn (): Response => (new EnsureFeatureIsActive)->handle(
                new Request,
                fn (Request $req): Response => new Response('OK'),
                $feature,
            ))->toThrow(AccessDeniedHttpException::class);
        }
    });

    it('lets the registry win when a disabled registry feature is active in Pennant', function (): void {
        config()->set('iam.features.registry-wins-false', false);
        Feature::define('iam.registry-wins-false', true);

        expect(fn (): Response => (new EnsureFeatureIsActive)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            'iam.registry-wins-false',
        ))->toThrow(AccessDeniedHttpException::class);
    });

    it('lets the registry win when an enabled registry feature is inactive in Pennant', function (): void {
        config()->set('iam.features.registry-wins-true', true);
        Feature::define('iam.registry-wins-true', false);

        $response = (new EnsureFeatureIsActive)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            'iam.registry-wins-true',
        );

        expect($response->getStatusCode())->toBe(Response::HTTP_OK);
    });
});
