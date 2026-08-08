<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set application locale based on the incoming Accept-Language header.
 *
 * Resolves available locales from the lang/ directory, caches them
 * for 24 hours, and sets the best matching locale on the App facade.
 */
final readonly class SetLocaleMiddleware
{
    /**
     * @var array<int, string>
     */
    private array $availableLocales;

    public function __construct()
    {
        $this->availableLocales = $this->resolveLocales();
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->getPreferredLanguage($this->availableLocales);

        if ($locale !== null && in_array($locale, $this->availableLocales, true)) {
            App::setLocale($locale);
        } else {
            App::setLocale(config()->string('app.locale', 'en'));
        }

        return $next($request);
    }

    /**
     * Load available locales from cache or fall back to scanning filesystem.
     *
     * @return array<int, string>
     */
    private function resolveLocales(): array
    {
        /** @var array<int, string> $locales */
        $locales = Cache::remember('app.available_locales', 86400, function (): array {
            $paths = glob(lang_path('*'), GLOB_ONLYDIR);
            $directories = $paths !== false ? $paths : [];

            $locales = array_map('basename', $directories);
            sort($locales);

            return $locales !== [] ? $locales : ['en'];
        });

        return $locales;
    }
}
