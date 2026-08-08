<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
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
        return self::applyTraceId($next($request), $request);
    }

    /**
     * Resolve the incoming trace ID (or generate a ULID), share it with
     * Laravel Context, and attach it as the X-Trace-ID response header.
     *
     * Also called from exception rendering, where middleware never runs.
     */
    public static function applyTraceId(Response $response, Request $request): Response
    {
        $incoming = $request->header('X-Trace-ID');
        $traceId = is_string($incoming) && Str::isUlid($incoming)
            ? $incoming
            : Str::ulid()->toString();

        // Laravel Context is shared with log records automatically
        Context::add('trace_id', $traceId);

        $response->headers->set('X-Trace-ID', $traceId);

        return $response;
    }
}
