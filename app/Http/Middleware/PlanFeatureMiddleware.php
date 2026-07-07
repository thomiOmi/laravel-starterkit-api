<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class PlanFeatureMiddleware
{
    /**
     * Gate the route behind a Pennant feature flag.
     *
     * Usage: `Route::get(...)->middleware('plan.feature:beta-feature')`
     *
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
