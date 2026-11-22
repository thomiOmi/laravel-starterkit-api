<?php

declare(strict_types=1);

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\AddTraceId;
use App\Http\Middleware\SetLocale;
use App\Http\Responses\ProblemResponse;
use Modules\IAM\Providers\IAMServiceProvider;

covers([AddTraceId::class, AddSecurityHeaders::class, SetLocale::class, ProblemResponse::class, IAMServiceProvider::class]);

describe('global api middleware pipeline', function (): void {
    it('renders missing api routes as problem responses with trace id and security headers', function (): void {
        $response = $this->getJson('/api/v1/__missing__');

        assertProblemResponse($response, 404, 'resource-not-found');
        assertHasTraceId($response);

        expect($response->headers->get('X-Trace-ID'))->toBeUlid();

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->assertHeader('Cache-Control', 'no-store, private');
    })->group('smoke');

    it('does not send HSTS outside production', function (): void {
        $response = $this->getJson('/api/v1/__missing__');

        expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
    });

});
