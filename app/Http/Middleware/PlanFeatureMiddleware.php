<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlanFeatureMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (! tenancy()->initialized) {
            return $next($request);
        }

        $tenant = tenant();

        if (! $tenant->hasFeature($feature)) {
            return response()->json([
                'status' => 'error',
                'message' => "Your current plan does not include the '{$feature}' feature. Please upgrade your subscription.",
                'code' => 'FEATURE_NOT_IN_PLAN',
            ], 403);
        }

        return $next($request);
    }
}
