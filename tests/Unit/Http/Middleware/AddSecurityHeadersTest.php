<?php

declare(strict_types=1);

use App\Http\Middleware\AddSecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

covers(AddSecurityHeaders::class);

function handleMiddleware(Request $request): Response
{
    return (new AddSecurityHeaders)->handle($request, fn (Request $req): Response => new Response('OK'));
}

describe('AddSecurityHeaders', function () {

    it('sets X-Content-Type-Options header', function () {
        expect(handleMiddleware(new Request)->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    });

    it('sets Referrer-Policy header', function () {
        expect(handleMiddleware(new Request)->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
    });

    it('sets X-Frame-Options header', function () {
        expect(handleMiddleware(new Request)->headers->get('X-Frame-Options'))->toBe('DENY');
    });

    it('sets Permissions-Policy header', function () {
        expect(handleMiddleware(new Request)->headers->get('Permissions-Policy'))->toBe('camera=(), microphone=(), geolocation=()');
    });

    it('does not add HSTS in non-production environment', function () {
        expect(handleMiddleware(new Request)->headers->has('Strict-Transport-Security'))->toBeFalse();
    });

    it('adds HSTS in production environment', function () {
        app()->detectEnvironment(fn () => 'production');

        expect(handleMiddleware(new Request)->headers->get('Strict-Transport-Security'))->toBe('max-age=31536000; includeSubDomains');
    });

    it('does not modify existing response content', function () {
        $middleware = new AddSecurityHeaders;

        $response = $middleware->handle(new Request, fn (Request $req): Response => new Response('Original body'));

        expect($response->getContent())->toBe('Original body');
    });

});
