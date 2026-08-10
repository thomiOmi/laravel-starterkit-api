<?php

declare(strict_types=1);

use App\Http\Middleware\Sunset;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

covers(Sunset::class);

describe('Sunset middleware', function (): void {
    it('attaches Deprecation and Sunset headers and passes the request through', function (): void {
        $response = (new Sunset)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            '2027-01-01',
        );

        $sunset = CarbonImmutable::parse('2027-01-01');

        expect($response->getStatusCode())->toBe(Response::HTTP_OK)
            ->and($response->headers->get('Deprecation'))->toBe('@'.$sunset->timestamp)
            ->and($response->headers->get('Sunset'))->toBe($sunset->utc()->format('D, d M Y H:i:s').' GMT');
    });

    it('adds a successor-version Link header when a URL is provided', function (): void {
        $response = (new Sunset)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            '2027-01-01',
            'https://v2.example.com/resource',
        );

        expect($response->headers->get('Link'))->toBe('<https://v2.example.com/resource>; rel="successor-version"');
    });

    it('enforces 410 when the sunset date has passed', function (): void {
        CarbonImmutable::setTestNow('2028-01-01 12:00:00');

        try {
            $response = (new Sunset)->handle(
                new Request,
                fn (Request $req): Response => new Response('OK'),
                '2027-01-01',
                'enforce',
            );

            expect($response->getStatusCode())->toBe(Response::HTTP_GONE)
                ->and($response->headers->get('Content-Type'))->toBe('application/problem+json')
                ->and($response->headers->get('Deprecation'))->not->toBeNull()
                ->and($response->headers->get('Sunset'))->not->toBeNull();
        } finally {
            CarbonImmutable::setTestNow();
        }
    });

    it('does not enforce before the sunset date', function (): void {
        CarbonImmutable::setTestNow('2026-01-01 12:00:00');

        try {
            $response = (new Sunset)->handle(
                new Request,
                fn (Request $req): Response => new Response('OK'),
                '2027-01-01',
                'enforce',
            );

            expect($response->getStatusCode())->toBe(Response::HTTP_OK);
        } finally {
            CarbonImmutable::setTestNow();
        }
    });

    it('resolves parameters regardless of order', function (): void {
        $response = (new Sunset)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            '2027-01-01',
            'https://v2.example.com/resource',
            'enforce',
        );

        expect($response->headers->get('Link'))->toBe('<https://v2.example.com/resource>; rel="successor-version"')
            ->and($response->getStatusCode())->toBe(Response::HTTP_OK);
    });

    it('throws when the date is invalid', function (): void {
        (new Sunset)->handle(
            new Request,
            fn (Request $req): Response => new Response('OK'),
            'not-a-date',
        );
    })->throws(InvalidArgumentException::class);
});
