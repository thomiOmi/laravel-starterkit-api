<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocaleMiddleware;

covers(SetLocaleMiddleware::class);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    Cache::forget('app.available_locales');
});

describe('SetLocaleMiddleware', function () {

    describe('locale resolution', function () {
        it('sets locale from Accept-Language header when matching available locale', function () {
            Cache::set('app.available_locales', ['en', 'id'], 86400);

            $request = new Request;
            $request->headers->set('Accept-Language', 'id');
            (new SetLocaleMiddleware)->handle($request, fn ($req): Response => new Response('OK'));

            expect(App::getLocale())->toBe('id');
        });

        it('falls back to default locale when Accept-Language has no match', function () {
            config()->set('app.locale', 'en');
            Cache::set('app.available_locales', ['en', 'id'], 86400);

            $request = new Request;
            $request->headers->set('Accept-Language', 'fr');
            (new SetLocaleMiddleware)->handle($request, fn ($req): Response => new Response('OK'));

            expect(App::getLocale())->toBe('en');
        });

        it('picks highest quality matching locale', function () {
            Cache::set('app.available_locales', ['en', 'id', 'de'], 86400);

            $request = new Request;
            $request->headers->set('Accept-Language', 'de-DE,de;q=0.9,id;q=0.8');
            (new SetLocaleMiddleware)->handle($request, fn ($req): Response => new Response('OK'));

            expect(App::getLocale())->toBe('de');
        });

        it('persists locale for the rest of the request lifecycle', function () {
            Cache::set('app.available_locales', ['id'], 86400);

            $request = new Request;
            $request->headers->set('Accept-Language', 'id');
            (new SetLocaleMiddleware)->handle($request, fn ($req): Response => new Response('OK'));

            expect(App::getLocale())->toBe('id');
        });
    });

    describe('caching', function () {
        it('caches locales from lang directory on first request', function () {
            Cache::forget('app.available_locales');

            (new SetLocaleMiddleware)->handle(new Request, fn ($req): Response => new Response('OK'));

            $cached = Cache::get('app.available_locales');
            expect($cached)->toBeArray();
            expect($cached)->toContain('en', 'id');
        });
    });

});
