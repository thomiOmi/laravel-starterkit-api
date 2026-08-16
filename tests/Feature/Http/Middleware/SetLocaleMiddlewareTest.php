<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocaleMiddleware;

covers(SetLocaleMiddleware::class);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    Cache::forget('set-locale.available_locales');
    config()->set('app.available_locales', ['en', 'id']);
});

describe('SetLocaleMiddleware', function () {

    describe('locale resolution', function () {
        it('sets locale from Accept-Language header when matching available locale', function () {
            config()->set('app.available_locales', ['en', 'id']);

            $request = new Request;
            $request->headers->set('Accept-Language', 'id');
            (new SetLocaleMiddleware)->handle($request, fn (Request $req): Response => new Response('OK'));

            expect(App::getLocale())->toBe('id');
        });

        it('falls back to default locale when Accept-Language has no match', function () {
            config()->set('app.locale', 'en');
            config()->set('app.available_locales', ['en', 'id']);

            $request = new Request;
            $request->headers->set('Accept-Language', 'fr');
            (new SetLocaleMiddleware)->handle($request, fn (Request $req): Response => new Response('OK'));

            expect(App::getLocale())->toBe('en');
        });

        it('picks highest quality matching locale', function () {
            config()->set('app.available_locales', ['en', 'id', 'de']);

            $request = new Request;
            $request->headers->set('Accept-Language', 'de-DE,de;q=0.9,id;q=0.8');
            (new SetLocaleMiddleware)->handle($request, fn (Request $req): Response => new Response('OK'));

            expect(App::getLocale())->toBe('de');
        });

        it('persists locale for the rest of the request lifecycle', function () {
            config()->set('app.available_locales', ['id']);

            $request = new Request;
            $request->headers->set('Accept-Language', 'id');
            (new SetLocaleMiddleware)->handle($request, fn (Request $req): Response => new Response('OK'));

            expect(App::getLocale())->toBe('id');
        });
    });

    describe('config resolution', function () {
        it('prefers configured locales over cached lang directory scan', function () {
            Cache::set('set-locale.available_locales', ['de'], 86400);
            config()->set('app.available_locales', ['en', 'id']);

            $request = new Request;
            $request->headers->set('Accept-Language', 'de');
            (new SetLocaleMiddleware)->handle($request, fn (Request $req): Response => new Response('OK'));

            expect(App::getLocale())->toBe('en');
        });

        it('filters out empty configured values', function () {
            config()->set('app.available_locales', ['en', '']);

            $request = new Request;
            $request->headers->set('Accept-Language', 'en');
            (new SetLocaleMiddleware)->handle($request, fn (Request $req): Response => new Response('OK'));

            expect(App::getLocale())->toBe('en');
        });

        it('caches locales from lang directory when config is empty', function () {
            config()->set('app.available_locales', []);

            (new SetLocaleMiddleware)->handle(new Request, fn (Request $req): Response => new Response('OK'));

            $cached = Cache::get('set-locale.available_locales');
            expect($cached)->toBeArray()
                ->toContain('en', 'id');
        });
    });

});
