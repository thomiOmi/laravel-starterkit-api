<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Gate route access behind a Pennant feature flag.
 *
 * Usage: `Route::get(...)->middleware('feature.flag:beta-feature')`
 * Returns 403 when the feature is inactive.
 */
final readonly class FeatureFlagMiddleware
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (! Feature::active($feature)) {
            throw new AccessDeniedHttpException(__('auth.forbidden'));
        }

        return $next($request);
    }
}
