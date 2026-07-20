<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Enums\PermissionEnum;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\SetLocaleMiddleware;
use App\Http\Middleware\Sunset;
use App\Http\Middleware\TraceIdMiddleware;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Feature;

test('TraceIdMiddleware adds ULID to response and context via HTTP', function () {
    Route::get('/test-trace', fn () => response()->json(['ok' => true]))
        ->middleware(TraceIdMiddleware::class);

    $response = $this->get('/test-trace');

    expect($response)->toHaveTraceId();

    $traceId = $response->headers->get('X-Trace-ID');
    expect(Context::get('trace_id'))->toBe($traceId);
});

describe('SunsetMiddleware', function () {
    it('adds RFC 7231 Sunset and RFC 9745 Deprecation headers', function () {
        $date = '2025-12-31';
        Route::get('/test-sunset', fn () => response()->json(['ok' => true]))
            ->middleware(Sunset::class.':'.$date);

        $response = $this->get('/test-sunset');

        $sunsetDate = CarbonImmutable::parse($date)->utc();
        expect($response)
            ->toHaveSunsetHeader($date)
            ->and($response->headers->get('Deprecation'))->toBe('@'.$sunsetDate->timestamp);
    });

    it('adds successor-version Link header when URL is provided', function () {
        $date = '2025-12-31';
        $url = 'https://api.example.com/v2/resource';
        Route::get('/test-sunset-link', fn () => response()->json(['ok' => true]))
            ->middleware(Sunset::class.':'.$date.','.$url);

        $response = $this->get('/test-sunset-link');

        expect($response->headers->get('Link'))->toBe('<'.$url.'>; rel="successor-version"');
    });

    it('returns 410 Gone when enforced after sunset', function () {
        $date = '2024-01-01';
        Route::get('/test-sunset-enforce', fn () => response()->json(['ok' => true]))
            ->middleware(Sunset::class.':'.$date.',enforce');

        $response = $this->get('/test-sunset-enforce');

        expect($response)
            ->toBeProblemResponse(status: 410)
            ->and($response->headers->has('Sunset'))->toBeTrue()
            ->and($response->headers->has('Deprecation'))->toBeTrue();
    });
});

describe('PlanFeatureMiddleware', function () {
    it('allows access when feature is active', function () {
        Feature::activate('test-feature');
        Route::get('/test-feature-active', fn () => response()->json(['ok' => true]))
            ->middleware('feature.flag:test-feature');

        $response = $this->get('/test-feature-active');

        expect($response->status())->toBe(200);
    });

    it('denies access when feature is inactive', function () {
        Feature::deactivate('test-feature');
        Route::get('/test-feature-inactive', fn () => response()->json(['ok' => true]))
            ->middleware('feature.flag:test-feature');

        $response = $this->get('/test-feature-inactive');

        expect($response)->toBeProblemResponse(status: 403);
    });
});

test('SpatiePermissionMiddleware denies user without permission', function () {
    $user = loginAsUser();

    Route::get('/test-permission', fn () => response()->json(['ok' => true]))
        ->middleware(['auth:sanctum', 'permission:'.PermissionEnum::RoleView->value]);

    $response = $this->get('/test-permission');

    expect($response)->toBeProblemResponse(status: 403);
})->group('v1');

test('SetLocaleMiddleware handles Accept-Language via HTTP', function () {
    Route::get('/test-locale', fn () => response()->json(['locale' => app()->getLocale()]))
        ->middleware(SetLocaleMiddleware::class);

    $response = $this->get('/test-locale', ['Accept-Language' => 'en']);
    expect($response->json('locale'))->toBe('en');
});

describe('SecurityHeadersMiddleware', function () {
    it('adds standard security headers to response', function () {
        Route::get('/test-security-headers', fn () => response()->json(['ok' => true]))
            ->middleware(SecurityHeadersMiddleware::class);

        $response = $this->get('/test-security-headers');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    });

    it('adds HSTS header in production environment', function () {
        app()->useEnvironmentPath(__DIR__);
        $original = app()->environment();
        putenv('APP_ENV=production');
        app()->detectEnvironment(fn () => 'production');

        Route::get('/test-hsts', fn () => response()->json(['ok' => true]))
            ->middleware(SecurityHeadersMiddleware::class);

        $response = $this->get('/test-hsts');

        expect($response->headers->get('Strict-Transport-Security'))
            ->toBe('max-age=31536000; includeSubDomains');

        putenv("APP_ENV={$original}");
        app()->detectEnvironment(fn () => $original);
    });

    it('does not add HSTS header outside production', function () {
        Route::get('/test-no-hsts', fn () => response()->json(['ok' => true]))
            ->middleware(SecurityHeadersMiddleware::class);

        $response = $this->get('/test-no-hsts');

        expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
    });
});

describe('EnsureEmailIsVerified', function () {
    it('returns 401 when no authenticated user', function () {
        Route::get('/test-verified-protected', fn () => response()->json(['ok' => true]))
            ->middleware(EnsureEmailIsVerified::class);

        $response = $this->get('/test-verified-protected');

        expect($response)->toBeProblemResponse(status: 401);
    });
});
