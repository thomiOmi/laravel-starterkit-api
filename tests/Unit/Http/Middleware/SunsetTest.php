<?php

declare(strict_types=1);

use App\Http\Middleware\Sunset;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

covers(Sunset::class);

function handleSunset(string $sunsetAt, string ...$params): Response
{
    $middleware = new Sunset;

    return $middleware->handle(
        new Request,
        fn (Request $req): Response => new Response('OK'),
        $sunsetAt,
        ...$params,
    );
}

describe('Sunset Middleware', function () {

    describe('headers', function () {
        it('adds Deprecation header with unix timestamp', function () {
            $response = handleSunset('+30 days');

            expect($response->headers->has('Deprecation'))->toBeTrue()
                ->and($response->headers->get('Deprecation'))->toMatch('/^@\d+$/');
        });

        it('adds Sunset header with RFC 1123 date', function () {
            $response = handleSunset('2027-01-15');

            $sunset = $response->headers->get('Sunset');
            expect($sunset)->toMatch('/^[A-Z][a-z]{2}, \d{2} [A-Z][a-z]{2} \d{4} \d{2}:\d{2}:\d{2} GMT$/');
        });

        it('Deprecation and Sunset timestamps match the given date', function () {
            $date = '2027-06-15';
            $response = handleSunset($date);

            $deprecation = (int) str_replace('@', '', $response->headers->get('Deprecation') ?? '');
            $expected = CarbonImmutable::parse($date)->timestamp;

            expect($deprecation)->toBe($expected);
        });
    });

    describe('successor URL', function () {
        it('adds Link header with successor-version when URL is provided', function () {
            $response = handleSunset('2027-01-01', 'https://v2.example.com/resource');

            expect($response->headers->get('Link'))->toContain('rel="successor-version"')
                ->toContain('https://v2.example.com/resource');
        });

        it('does not add Link header without successor URL', function () {
            $response = handleSunset('2027-01-01');

            expect($response->headers->has('Link'))->toBeFalse();
        });

        it('successor URL is order-independent', function () {
            $response = handleSunset('2027-01-01', 'enforce', 'https://v2.example.com/resource');

            expect($response->headers->get('Link'))->toContain('https://v2.example.com/resource');
        });
    });

    describe('enforce', function () {
        it('returns 410 Gone when enforce and after sunset', function () {
            $past = CarbonImmutable::now()->subDay()->format('Y-m-d');
            $response = handleSunset($past, 'enforce');

            expect($response->getStatusCode())->toBe(Response::HTTP_GONE)
                ->and($response->getContent())->toContain('sunset');
        });

        it('passes through when enforce but before sunset', function () {
            $future = CarbonImmutable::now()->addYear()->format('Y-m-d');
            $response = handleSunset($future, 'enforce');

            expect($response->getStatusCode())->toBe(Response::HTTP_OK)
                ->and($response->getContent())->toBe('OK');
        });

        it('passes through without enforce even when past sunset', function () {
            $past = CarbonImmutable::now()->subDay()->format('Y-m-d');
            $response = handleSunset($past);

            expect($response->getStatusCode())->toBe(Response::HTTP_OK)
                ->and($response->getContent())->toBe('OK');
        });
    });

    it('enforce response includes Deprecation, Sunset, and Link headers', function () {
        $past = CarbonImmutable::now()->subDay()->format('Y-m-d');
        $response = handleSunset($past, 'enforce', 'https://v2.example.com');

        expect($response->headers->has('Deprecation'))->toBeTrue()
            ->and($response->headers->has('Sunset'))->toBeTrue()
            ->and($response->headers->has('Link'))->toBeTrue();
    });

    it('passes request through to next handler when not enforcing', function () {
        $response = handleSunset('+30 days');

        expect($response->getContent())->toBe('OK');
    });

});
