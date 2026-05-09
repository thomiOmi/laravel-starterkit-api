<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to set the application locale based on the Accept-Language header.
 */
class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) $request->header('Accept-Language');

        /** @var array<int, string> $availableLocales */
        $availableLocales = config('app.available_locales', ['en', 'id']);

        if ($locale !== '' && in_array($locale, $availableLocales, true)) {
            App::setLocale($locale);
        } else {
            /** @var string $defaultLocale */
            $defaultLocale = config('app.locale', 'en');
            App::setLocale($defaultLocale);
        }

        return $next($request);
    }
}
