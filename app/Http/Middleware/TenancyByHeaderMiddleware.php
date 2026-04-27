<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

class TenancyByHeaderMiddleware extends InitializeTenancyByRequestData
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->method() !== 'OPTIONS') {
            $tenantId = $this->getPayload($request);
            if ($tenantId) {
                tenancy()->initialize($tenantId);

                // Clear auth cache if tenant changes
                foreach (config('auth.guards') as $guard => $config) {
                    try {
                        auth()->guard($guard)->forgetUser();
                    } catch (\Throwable $e) {
                        // Guard might not support forgetUser()
                    }
                }
            }
        }

        $response = $next($request);

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        return $response;
    }
}
