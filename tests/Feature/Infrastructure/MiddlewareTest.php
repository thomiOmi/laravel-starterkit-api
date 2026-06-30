<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Http\Middleware\SetLocaleMiddleware;
use App\Http\Middleware\Sunset;
use App\Http\Middleware\TraceIdMiddleware;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

test('TraceIdMiddleware adds ULID to response and context via HTTP', function () {
    /** @var TestCase $this */
    Route::get('/test-trace', fn () => response()->json(['ok' => true]))
        ->middleware(TraceIdMiddleware::class);

    $response = $this->get('/test-trace');

    expect($response)->toHaveTraceId();

    $traceId = $response->headers->get('X-Trace-ID');
    expect(Context::get('trace_id'))->toBe($traceId);
});

test('SunsetMiddleware adds RFC 7231 header via HTTP', function () {
    /** @var TestCase $this */
    $date = '2025-12-31';
    Route::get('/test-sunset', fn () => response()->json(['ok' => true]))
        ->middleware(Sunset::class.':'.$date);

    $response = $this->get('/test-sunset');

    expect($response)->toHaveSunsetHeader($date);
});

test('SetLocaleMiddleware handles Accept-Language via HTTP', function () {
    /** @var TestCase $this */
    Route::get('/test-locale', fn () => response()->json(['locale' => app()->getLocale()]))
        ->middleware(SetLocaleMiddleware::class);

    $response = $this->get('/test-locale', ['Accept-Language' => 'en']);
    expect($response->json('locale'))->toBe('en');
});
