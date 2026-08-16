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
 * Available locales are read from `app.available_locales` (single source
 * of truth). When that config is empty, locales are resolved from the
 * lang/ directory and cached for 24 hours.
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
     * Resolve the available locales from config, falling back to scanning
     * the lang/ directory when config is empty.
     *
     * @return array<int, string>
     */
    private function resolveLocales(): array
    {
        $configured = array_values(array_filter(
            config()->array('app.available_locales', []),
            fn (mixed $locale): bool => is_string($locale) && $locale !== ''
        ));

        if ($configured !== []) {
            return $configured;
        }

        /** @var array<int, string> $locales */
        $locales = Cache::remember('set-locale.available_locales', 86400, function (): array {
            $paths = glob(lang_path('*'), GLOB_ONLYDIR);
            $directories = $paths !== false ? $paths : [];

            $locales = array_map('basename', $directories);
            sort($locales);

            return $locales !== [] ? $locales : ['en'];
        });

        return $locales;
    }
}
