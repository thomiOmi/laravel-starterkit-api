<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\ApiKey\Models\ApiKey;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $headerKey = $request->header('X-API-Key');

        if (! $headerKey) {
            return $next($request);
        }

        // Remove prefix if exists
        $plainKey = str_replace('sk_live_', '', $headerKey);
        $hashedKey = hash('sha256', $plainKey);

        $apiKey = ApiKey::where('key', $hashedKey)->first();

        if (! $apiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API Key.',
            ], 401);
        }

        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return response()->json([
                'status' => 'error',
                'message' => 'API Key has expired.',
            ], 401);
        }

        // IP Whitelisting
        if (! empty($apiKey->ip_whitelist)) {
            $clientIp = $request->ip();
            if (! in_array($clientIp, $apiKey->ip_whitelist)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'IP address not allowed.',
                ], 403);
            }
        }

        // Authenticate the user associated with the API Key
        /** @var Authenticatable $user */
        $user = $apiKey->user;
        auth()->login($user);

        // Update last used at
        $apiKey->update(['last_used_at' => now()]);

        return $next($request);
    }
}
