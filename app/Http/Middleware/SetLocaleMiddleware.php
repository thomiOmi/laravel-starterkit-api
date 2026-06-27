<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /** @var array<int, string> */
    private array $availableLocales;

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->resolveLocales();

        $locale = $request->getPreferredLanguage($this->availableLocales);

        if ($locale !== null && in_array($locale, $this->availableLocales, true)) {
            App::setLocale($locale);
        } else {
            App::setLocale(config()->string('app.locale', 'en'));
        }

        return $next($request);
    }

    private function resolveLocales(): void
    {
        $cached = Cache::get('app.available_locales');

        if (is_array($cached)) {
            $this->availableLocales = [];
            foreach ($cached as $locale) {
                if (is_string($locale)) {
                    $this->availableLocales[] = $locale;
                }
            }
            if ($this->availableLocales !== []) {
                return;
            }
        }

        $this->buildLocales();
    }

    private function buildLocales(): void
    {
        $directories = glob(lang_path('*'), GLOB_ONLYDIR) ?: [];

        $this->availableLocales = array_map('basename', $directories);
        sort($this->availableLocales);

        if ($this->availableLocales === []) {
            $this->availableLocales = ['en'];
        }

        Cache::set('app.available_locales', $this->availableLocales, 86400);
    }
}
