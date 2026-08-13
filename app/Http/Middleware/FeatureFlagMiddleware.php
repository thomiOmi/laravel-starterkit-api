<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Gate route access behind a build-time or runtime feature flag.
 *
 * Usage: `Route::get(...)->middleware('feature.flag:iam.self-registration')`
 *
 * The flag is resolved in two ways:
 * - Build-time: `{alias}.{feature}` resolves to the central registry features
 *   array, merged by the base ModuleServiceProvider into
 *   `config('{alias}.features.{feature}')`.
 * - Runtime: any other name falls back to a Pennant feature
 *   (Feature::active), e.g. a class under app/Features/ or a module
 *   Features/ directory.
 *
 * Returns 403 when the flag is off.
 */
final readonly class FeatureFlagMiddleware
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (! $this->isActive($feature)) {
            throw new AccessDeniedHttpException(__('auth.http_forbidden'));
        }

        return $next($request);
    }

    /**
     * Resolve a build-time registry feature first, then fall back to Pennant.
     */
    protected function isActive(string $feature): bool
    {
        $parts = explode('.', $feature, 2);

        if (count($parts) === 2) {
            [$alias, $name] = $parts;

            if (config()->has("{$alias}.features.{$name}")) {
                return config()->boolean("{$alias}.features.{$name}", false);
            }
        }

        return Feature::active($feature);
    }
}
