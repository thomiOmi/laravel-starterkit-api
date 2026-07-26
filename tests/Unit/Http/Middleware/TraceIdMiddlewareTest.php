<?php

declare(strict_types=1);

use App\Http\Middleware\TraceIdMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

describe('TraceIdMiddleware', function () {

    it('adds X-Trace-ID header to response', function () {
        $response = (new TraceIdMiddleware)->handle(new Request, fn ($req): Response => new Response('OK'));

        expect($response->headers->has('X-Trace-ID'))->toBeTrue();
        expect($response->headers->get('X-Trace-ID'))->toBeString()->not->toBeEmpty();
    });

    it('trace ID is a ULID', function () {
        $response = (new TraceIdMiddleware)->handle(new Request, fn ($req): Response => new Response('OK'));

        expect(Str::isUlid($response->headers->get('X-Trace-ID')))->toBeTrue();
    });

    it('adds trace ID to Laravel Context', function () {
        $response = (new TraceIdMiddleware)->handle(new Request, fn ($req): Response => new Response('OK'));

        $traceId = $response->headers->get('X-Trace-ID');

        expect(Context::get('trace_id'))->toBe($traceId);
    });

    it('trace ID is unique per request', function () {
        $first = (new TraceIdMiddleware)->handle(new Request, fn ($req): Response => new Response('OK'))->headers->get('X-Trace-ID');
        $second = (new TraceIdMiddleware)->handle(new Request, fn ($req): Response => new Response('OK'))->headers->get('X-Trace-ID');

        expect($first)->not->toBe($second);
    });

    it('does not modify existing response content', function () {
        $response = (new TraceIdMiddleware)->handle(new Request, fn ($req): Response => new Response('Original body'));

        expect($response->getContent())->toBe('Original body');
    });

});
