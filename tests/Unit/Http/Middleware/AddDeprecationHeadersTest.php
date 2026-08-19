<?php

declare(strict_types=1);

use App\Http\Middleware\AddDeprecationHeaders;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

covers(AddDeprecationHeaders::class);

function handleDeprecation(string $sunsetAt, string ...$params): Response
{
    $middleware = new AddDeprecationHeaders;

    return $middleware->handle(
        new Request,
        fn (Request $req): Response => new Response('OK'),
        $sunsetAt,
        ...$params,
    );
}

describe('AddDeprecationHeaders middleware', function (): void {
    describe('headers', function (): void {
        it('adds Deprecation header with unix timestamp', function (): void {
            $response = handleDeprecation('2099-01-01');

            expect($response->headers->has('Deprecation'))->toBeTrue()
                ->and($response->headers->get('Deprecation'))->toMatch('/^@\d+$/');
        });

        it('adds Sunset header with RFC 1123 date', function (): void {
            $response = handleDeprecation('2027-01-15');

            expect($response->headers->get('Sunset'))->toMatch('/^[A-Z][a-z]{2}, \d{2} [A-Z][a-z]{2} \d{4} \d{2}:\d{2}:\d{2} GMT$/');
        });

        it('Deprecation and Sunset timestamps match the given date', function (): void {
            $date = '2027-06-15';
            $response = handleDeprecation($date);

            $deprecation = (int) str_replace('@', '', $response->headers->get('Deprecation') ?? '');
            $expected = CarbonImmutable::parse($date)->timestamp;

            expect($deprecation)->toBe($expected);
        });
    });

    describe('successor URL', function (): void {
        it('adds Link header with successor-version when URL is provided', function (): void {
            $response = handleDeprecation('2027-01-01', 'https://v2.example.com/resource');

            expect($response->headers->get('Link'))->toContain('rel="successor-version"')
                ->toContain('https://v2.example.com/resource');
        });

        it('does not add Link header without successor URL', function (): void {
            $response = handleDeprecation('2027-01-01');

            expect($response->headers->has('Link'))->toBeFalse();
        });

        it('successor URL is order-independent', function (): void {
            $response = handleDeprecation('2027-01-01', 'enforce', 'https://v2.example.com/resource');

            expect($response->headers->get('Link'))->toContain('https://v2.example.com/resource');
        });
    });

    describe('enforce', function (): void {
        it('returns 410 Gone when enforce and after sunset', function (): void {
            $past = CarbonImmutable::now()->subDay()->format('Y-m-d');
            $response = handleDeprecation($past, 'enforce');

            expect($response->getStatusCode())->toBe(Response::HTTP_GONE)
                ->and($response->getContent())->toContain('sunset')
                ->and($response->headers->get('Content-Type'))->toBe('application/problem+json')
                ->and($response->headers->has('Deprecation'))->toBeTrue()
                ->and($response->headers->has('Sunset'))->toBeTrue();
        });

        it('passes through when enforce but before sunset', function (): void {
            $future = CarbonImmutable::now()->addYear()->format('Y-m-d');
            $response = handleDeprecation($future, 'enforce');

            expect($response->getStatusCode())->toBe(Response::HTTP_OK)
                ->and($response->getContent())->toBe('OK');
        });

        it('passes through without enforce even when past sunset', function (): void {
            $past = CarbonImmutable::now()->subDay()->format('Y-m-d');
            $response = handleDeprecation($past);

            expect($response->getStatusCode())->toBe(Response::HTTP_OK)
                ->and($response->getContent())->toBe('OK');
        });
    });

    it('passes request through to next handler when not enforcing', function (): void {
        $response = handleDeprecation('2099-01-01');

        expect($response->getContent())->toBe('OK');
    });

    it('throws InvalidArgumentException when the date does not match Y-m-d', function (): void {
        expect(fn () => handleDeprecation('+30 days'))->toThrow(InvalidArgumentException::class, 'expected format Y-m-d');
    });
});
