<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SunsetMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $date): Response
    {
        $response = $next($request);

        $response->headers->set('Sunset', $date);
        $response->headers->set(
            'Deprecation',
            'This API version is deprecated and will be removed on ' . $date
        );

        return $response;
    }
}
