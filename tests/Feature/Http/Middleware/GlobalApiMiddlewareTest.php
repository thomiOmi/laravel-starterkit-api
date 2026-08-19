<?php

declare(strict_types=1);

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\AddTraceId;
use App\Http\Middleware\SetLocale;
use App\Http\Responses\ProblemResponse;
use Modules\IAM\Providers\IAMServiceProvider;

covers([AddTraceId::class, AddSecurityHeaders::class, SetLocale::class, ProblemResponse::class, IAMServiceProvider::class]);

describe('global api middleware pipeline', function () {

    it('renders missing api routes as problem responses with trace id and security headers', function () {
        $response = $this->getJson('/api/v1/__missing__');

        assertProblemResponse($response, 404, 'resource-not-found');
        assertHasTraceId($response);

        expect($response->headers->get('X-Trace-ID'))->toBeUlid()
            ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
            ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
            ->and($response->headers->get('X-Frame-Options'))->toBe('DENY')
            ->and($response->headers->get('Permissions-Policy'))->toBe('camera=(), microphone=(), geolocation=()')
            ->and($response->headers->get('Cache-Control'))->toBe('no-store, private');
    })->group('smoke');

    it('does not send HSTS outside production', function () {
        $response = $this->getJson('/api/v1/__missing__');

        expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
    });

});
