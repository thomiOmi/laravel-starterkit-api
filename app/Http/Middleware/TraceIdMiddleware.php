<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final readonly class TraceIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $traceId = (string) Str::ulid();

        // Store in Laravel Context for logging and tracing
        Context::add('trace_id', $traceId);

        $response = $next($request);

        // Append to response headers
        $response->headers->set('X-Trace-ID', $traceId);

        return $response;
    }
}
