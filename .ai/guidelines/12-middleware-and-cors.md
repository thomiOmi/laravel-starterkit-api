# Middleware & CORS Standards

We use middleware to enforce API-specific requirements and handle cross-origin resource sharing (CORS).

## 1. Custom Middleware

### ForceJsonResponse
Ensures the API always returns JSON by setting the `Accept: application/json` header on all requests. This must be the **first** middleware applied to any API route group.

```php
// app/Http/Middleware/ForceJsonResponse.php
public function handle(Request $request, Closure $next): Response
{
    $request->headers->set('Accept', 'application/json');
    return $next($request);
}
```

### Sunset (RFC 8594)
Signals endpoint deprecation by attaching a `Sunset` header to the response.

```php
// Applied with a date parameter: 'sunset:2026-12-31'
$response->headers->set(
    'Sunset',
    (new DateTimeImmutable($date))->format(DateTimeInterface::RFC7231),
);
```

## 2. CORS (Cross-Origin Resource Sharing)

Standalone APIs are often called from different origins. We configure CORS globally but drive it through environment variables for security and flexibility.

### Configuration (`config/cors.php`):
```php
return [
    'paths'                    => ['api/*'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => false,
];
```

### Environment Variable (`.env`):
```text
CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com
```

## 3. Middleware Stack Order

Always apply middleware in this order to ensure errors are caught and returned as JSON:
1. `force.json` (Custom)
2. `auth:sanctum`
3. `throttle:api`
4. `sunset:YYYY-MM-DD` (Optional, for deprecated routes)

## 4. Anti-Patterns

- ❌ Do not use `allowed_origins => ['*']` in production.
- ❌ Do not apply `auth:sanctum` before `force.json`.
- ❌ Do not hardcode allowed origins in the configuration file.
- ❌ Do not forget to include `throttle:api` on all public or authenticated routes.
