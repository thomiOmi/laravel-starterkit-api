# Rate Limiting Standards

Every API endpoint must be protected by rate limiting to prevent abuse and ensure service availability.

## 1. Global Throttling

All API route groups must include the `throttle:api` middleware.

```php
Route::prefix('v1/posts')
    ->middleware(['force.json', 'auth:sanctum', 'throttle:api'])
    ->group(...);
```

## 2. Configuration

Rate limiters are defined in `App\Providers\AppServiceProvider` (or a dedicated `RateLimitServiceProvider`).

### Default Limiter:
```php
RateLimiter::for('api', function (Request $request): Limit {
    return Limit::perMinute(60)->by(
        key: $request->user()?->id ?: $request->ip(),
    );
});
```

## 3. Specialized Limiters

Create specialized limiters for sensitive endpoints like Login or Registration:

```php
RateLimiter::for('auth', function (Request $request): Limit {
    return Limit::perMinute(10)->by($request->ip());
});
```

## 4. Why Throttling is Mandatory?

1. **Security**: Prevents brute-force attacks on auth endpoints.
2. **Stability**: Protects the server from being overwhelmed by buggy clients or malicious scrapers.
3. **Fairness**: Ensures that no single user can consume all available system resources.

## 5. Anti-Patterns

- ❌ Do not leave any public endpoint unthrottled.
- ❌ Do not use a single global limit for all endpoints if some are more sensitive (like login).
- ❌ Do not forget to return the standard rate-limit headers (Laravel handles this automatically).
