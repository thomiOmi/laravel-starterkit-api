<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generate a ULID trace ID for every request.
 *
 * Stores the trace ID in Laravel Context and Monolog for structured
 * logging, and appends it as the X-Trace-ID response header.
 */
final readonly class TraceIdMiddleware
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $traceId = Str::ulid()->toString();

        // Store in Laravel Context for logging and tracing
        Context::add('trace_id', $traceId);

        // Share with Monolog for every log line
        Log::withContext([
            'trace_id' => $traceId,
        ]);

        $response = $next($request);

        // Append to response headers
        $response->headers->set('X-Trace-ID', $traceId);

        return $response;
    }
}
