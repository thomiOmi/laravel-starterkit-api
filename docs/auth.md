# Authentication

All auth endpoints are under `/api/v1/auth/`. Uses Sanctum token-based auth with device tracking.

## Endpoints

| Method | Path | Auth | Rate Limit | Description |
|--------|------|------|------------|-------------|
| POST | `/auth/register` | No | `auth` (5/min/email + 10/min/IP) | Register new user |
| POST | `/auth/login` | No | `auth` | Login, returns Bearer token |
| POST | `/auth/logout` | Yes | `authenticated` (120/min) | Revoke current token |
| GET | `/auth/me` | Yes | `authenticated` | Get authenticated user with roles/permissions |
| POST | `/auth/forgot-password` | No | `auth` | Send password reset link |
| POST | `/auth/reset-password` | No | `auth` | Reset password with token |
| GET | `/auth/verify-email/{id}/{hash}` | No (signed) | `api` (60/min) | Verify email via signed URL |
| POST | `/auth/email/verification-notification` | Yes | `authenticated` | Re-send verification email |
| GET | `/auth/devices` | Yes | `authenticated` | List active sessions/devices |
| DELETE | `/auth/devices/{device}` | Yes | `authenticated` | Revoke a specific device token |
| POST | `/auth/devices/logout-others` | Yes | `authenticated` | Revoke all other device tokens |

## Social Login

Supported providers: `google`, `github`.

```
GET /api/v1/auth/social/{provider}/redirect
GET /api/v1/auth/social/{provider}/callback
```

The redirect endpoint returns the OAuth provider URL. The callback handles user matching:
1. Match by `provider` + `provider_id`
2. Match by `email` (link existing account)
3. Create new user with `password = null`

Configure credentials in `config/services.php` via `.env`:
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`
- `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`, `GITHUB_REDIRECT_URI`

## Email Verification

New users get `email_verified_at = null` by default. Verification uses signed URLs generated via `URL::temporarySignedRoute()`. The frontend URL is configured via `APP_FRONTEND_URL` in `.env`.

## Password Reset

Uses Laravel's built-in password broker. Reset link URLs are customized to point at `APP_FRONTEND_URL/reset-password?token=X&email=Y`. Password rules are configured in `AppServiceProvider` via `Password::defaults()` (min 8 chars, mixed case in production).
