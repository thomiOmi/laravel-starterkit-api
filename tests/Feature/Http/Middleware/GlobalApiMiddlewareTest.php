<?php

declare(strict_types=1);

use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\SetLocaleMiddleware;
use App\Http\Middleware\TraceIdMiddleware;
use App\Http\Responses\ProblemResponse;
use Modules\IAM\Providers\IAMServiceProvider;

covers([TraceIdMiddleware::class, SecurityHeadersMiddleware::class, SetLocaleMiddleware::class, ProblemResponse::class, IAMServiceProvider::class]);

describe('global api middleware pipeline', function () {

    it('renders missing api routes as problem responses with trace id and security headers', function () {
        $response = $this->getJson('/api/v1/__missing__');

        assertProblemResponse($response, 404, 'resource-not-found');
        assertHasTraceId($response);

        expect($response->headers->get('X-Trace-ID'))->toBeUlid()
            ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
            ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
            ->and($response->headers->get('X-Frame-Options'))->toBe('DENY')
            ->and($response->headers->get('Permissions-Policy'))->toBe('camera=(), microphone=(), geolocation=()');
    })->group('smoke');

    it('does not send HSTS outside production', function () {
        $response = $this->getJson('/api/v1/__missing__');

        expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
    });

});
