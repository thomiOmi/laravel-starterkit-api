<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocaleMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    Cache::forget('app.available_locales');
});

test('sets locale from Accept-Language header when matching available locale', function () {
    Cache::set('app.available_locales', ['en', 'id'], 86400);

    $request = new Request;
    $request->headers->set('Accept-Language', 'id');
    $middleware = new SetLocaleMiddleware;

    $middleware->handle($request, fn ($req): Response => new Response('OK'));

    expect(App::getLocale())->toBe('id');
});

test('falls back to default locale when Accept-Language has no match', function () {
    config()->set('app.locale', 'en');
    Cache::set('app.available_locales', ['en', 'id'], 86400);

    $request = new Request;
    $request->headers->set('Accept-Language', 'fr');
    $middleware = new SetLocaleMiddleware;

    $middleware->handle($request, fn ($req): Response => new Response('OK'));

    expect(App::getLocale())->toBe('en');
});

test('sets app locale from first matching accept language', function () {
    Cache::set('app.available_locales', ['en', 'id', 'de'], 86400);

    $request = new Request;
    $request->headers->set('Accept-Language', 'de-DE,de;q=0.9,id;q=0.8');
    $middleware = new SetLocaleMiddleware;

    $middleware->handle($request, fn ($req): Response => new Response('OK'));

    expect(App::getLocale())->toBe('de');
});

test('caches locales from lang directory on first request', function () {
    Cache::forget('app.available_locales');

    $middleware = new SetLocaleMiddleware;
    $middleware->handle(new Request, fn ($req): Response => new Response('OK'));

    $cached = Cache::get('app.available_locales');
    expect($cached)->toBeArray();
    expect($cached)->toContain('en', 'id');
});

test('persists locale for the rest of the request lifecycle', function () {
    Cache::set('app.available_locales', ['id'], 86400);

    $request = new Request;
    $request->headers->set('Accept-Language', 'id');
    $middleware = new SetLocaleMiddleware;

    $middleware->handle($request, fn ($req): Response => new Response('OK'));

    expect(App::getLocale())->toBe('id');
});
