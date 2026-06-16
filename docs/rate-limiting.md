# Rate Limiting

Tiered rate limiting configured in `config/rate-limiting.php` and `AppServiceProvider::configureRateLimiting()`.

## Tiers

| Limiter | Default Limit | Applied To | Key |
|---------|--------------|------------|-----|
| `auth` | 5/min per email + 10/min per IP | Register, Login, Forgot/Reset Password | Composite (email AND IP) |
| `authenticated` | 120/min | Authenticated routes (me, devices, logout) | User ID or IP |
| `api` | 60/min | General API (CRUD, verify email, social) | User ID or IP |

## Configuration (.env)

```
RATE_LIMIT_API=60
RATE_LIMIT_AUTH_PER_EMAIL=5
RATE_LIMIT_AUTH_PER_IP=10
RATE_LIMIT_AUTHENTICATED=120
```

All rate limits use a 1-minute decay window.

## Response Format

When rate limited, returns a `ProblemResponse` (RFC 9457) with status 429:

```json
{
    "type": "http://localhost/rate-limited",
    "title": "Too Many Requests",
    "status": 429,
    "message": "Too many requests.",
    "detail": "You have exceeded the allowed number of requests. Please try again later."
}
```
