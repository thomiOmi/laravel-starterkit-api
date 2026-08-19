<?php

declare(strict_types=1);

use App\Http\Middleware\AddTraceId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

covers(AddTraceId::class);

describe('AddTraceId', function () {

    it('adds X-Trace-ID header to response', function () {
        $response = (new AddTraceId)->handle(new Request, fn (Request $req): Response => new Response('OK'));

        expect($response->headers->has('X-Trace-ID'))->toBeTrue()
            ->and($response->headers->get('X-Trace-ID'))->toBeString()->not->toBeEmpty();
    });

    it('trace ID is a ULID', function () {
        $response = (new AddTraceId)->handle(new Request, fn (Request $req): Response => new Response('OK'));

        expect($response->headers->get('X-Trace-ID'))->toBeUlid();
    });

    it('adds trace ID to Laravel Context', function () {
        $response = (new AddTraceId)->handle(new Request, fn (Request $req): Response => new Response('OK'));

        $traceId = $response->headers->get('X-Trace-ID');

        expect(Context::get('trace_id'))->toBe($traceId);
    });

    it('trace ID is unique per request', function () {
        $first = (new AddTraceId)->handle(new Request, fn (Request $req): Response => new Response('OK'))->headers->get('X-Trace-ID');
        $second = (new AddTraceId)->handle(new Request, fn (Request $req): Response => new Response('OK'))->headers->get('X-Trace-ID');

        expect($first)->not->toBe($second);
    });

    it('does not modify existing response content', function () {
        $response = (new AddTraceId)->handle(new Request, fn (Request $req): Response => new Response('Original body'));

        expect($response->getContent())->toBe('Original body');
    });

});
