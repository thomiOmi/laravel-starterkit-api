<?php

declare(strict_types=1);

use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

function handleMiddleware(Request $request): Response
{
    $middleware = new SecurityHeadersMiddleware;

    return $middleware->handle($request, fn ($req): Response => new Response('OK'));
}

test('sets X-Content-Type-Options header', function () {
    $response = handleMiddleware(new Request);

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

test('sets Referrer-Policy header', function () {
    $response = handleMiddleware(new Request);

    expect($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
});

test('sets X-Frame-Options header', function () {
    $response = handleMiddleware(new Request);

    expect($response->headers->get('X-Frame-Options'))->toBe('DENY');
});

test('sets Permissions-Policy header', function () {
    $response = handleMiddleware(new Request);

    expect($response->headers->get('Permissions-Policy'))->toBe('camera=(), microphone=(), geolocation=()');
});

test('does not add HSTS in non-production environment', function () {
    $response = handleMiddleware(new Request);

    expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
});

test('adds HSTS in production environment', function () {
    app()->detectEnvironment(fn () => 'production');

    $response = handleMiddleware(new Request);

    expect($response->headers->get('Strict-Transport-Security'))->toBe('max-age=31536000; includeSubDomains');
});

test('does not modify existing response content', function () {
    $middleware = new SecurityHeadersMiddleware;

    $response = $middleware->handle(new Request, fn ($req): Response => new Response('Original body'));

    expect($response->getContent())->toBe('Original body');
});
