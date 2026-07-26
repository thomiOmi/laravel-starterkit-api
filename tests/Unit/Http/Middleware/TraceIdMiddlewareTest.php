<?php

declare(strict_types=1);

use App\Http\Middleware\TraceIdMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

test('adds X-Trace-ID header to response', function () {
    $middleware = new TraceIdMiddleware;

    $response = $middleware->handle(new Request, fn ($req): Response => new Response('OK'));

    expect($response->headers->has('X-Trace-ID'))->toBeTrue();
    expect($response->headers->get('X-Trace-ID'))->toBeString()->not->toBeEmpty();
});

test('trace ID is a ULID', function () {
    $middleware = new TraceIdMiddleware;

    $response = $middleware->handle(new Request, fn ($req): Response => new Response('OK'));

    $traceId = $response->headers->get('X-Trace-ID');
    expect(Str::isUlid($traceId))->toBeTrue();
});

test('adds trace ID to Laravel Context', function () {
    $middleware = new TraceIdMiddleware;

    $response = $middleware->handle(new Request, fn ($req): Response => new Response('OK'));

    $contextTraceId = Context::get('trace_id');
    expect($contextTraceId)->toBe($response->headers->get('X-Trace-ID'));
});

test('trace ID is unique per request', function () {
    $middleware = new TraceIdMiddleware;

    $first = $middleware->handle(new Request, fn ($req): Response => new Response('OK'))->headers->get('X-Trace-ID');
    $second = $middleware->handle(new Request, fn ($req): Response => new Response('OK'))->headers->get('X-Trace-ID');

    expect($first)->not->toBe($second);
});

test('does not modify existing response content', function () {
    $middleware = new TraceIdMiddleware;

    $response = $middleware->handle(new Request, fn ($req): Response => new Response('Original body'));

    expect($response->getContent())->toBe('Original body');
});
