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

### Setup Guide

#### 1. Register OAuth App

**Google:**
1. Go to https://console.cloud.google.com/apis/credentials
2. Create an **OAuth 2.0 Client ID** (Web application)
3. Add **Authorized redirect URIs**:
   - `http://localhost:8000/api/v1/auth/social/google/callback` (development)
   - `https://your-domain.com/api/v1/auth/social/google/callback` (production)
4. Save the **Client ID** and **Client Secret**

**GitHub:**
1. Go to https://github.com/settings/developers → **OAuth Apps** → **New OAuth App**
2. Fill in **Authorization callback URL**:
   - `http://localhost:8000/api/v1/auth/social/github/callback` (development)
   - `https://your-domain.com/api/v1/auth/social/github/callback` (production)
3. Save the **Client ID** and **Client Secret**

#### 2. Configure `.env`

Add these to your `.env` file:

```env
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/v1/auth/social/google/callback

GITHUB_CLIENT_ID=your-github-client-id
GITHUB_CLIENT_SECRET=your-github-client-secret
GITHUB_REDIRECT_URI=http://localhost:8000/api/v1/auth/social/github/callback
```

> **Note:** If using `php artisan serve` (default port 8000), the redirect URI must include the port. Adjust the port if using a different one.

#### 3. Test

```bash
# Step 1 - Get redirect URL (open in browser)
curl http://localhost:8000/api/v1/auth/social/google/redirect

# Step 2 - Browser redirects to Google login
# Step 3 - Google redirects back to callback with auth code
# Response: { status: 200, data: { user, access_token, token_type } }

# Step 4 - Use the token for authenticated requests
curl http://localhost:8000/api/v1/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {token}"
```

### Architecture

```
GET /api/v1/auth/social/{provider}/redirect
  └── SocialRedirectController::__invoke($provider)
       └── SocialRedirectAction::handle($provider)
            └── Socialite::driver($provider)->stateless()->redirect()->getTargetUrl()
            └── Returns: { url: "https://accounts.google.com/o/oauth2/auth?..." }

GET /api/v1/auth/social/{provider}/callback
  └── SocialCallbackController::__invoke($provider, $request)
       └── SocialCallbackAction::handle($provider, $ip, $userAgent)
            ├── Socialite::driver($provider)->stateless()->user()
            ├── DB::transaction() — find-or-create user
            │   ├── Match by provider + provider_id → return existing
            │   ├── Match by email → link provider + update avatar
            │   └── Create new user with password = null
            └── createToken() — Sanctum token with [*] abilities
            └── Returns: { user, access_token, token_type }
```

> Social users created via callback have `password = null` and cannot log in via the standard email/password flow.

## Email Verification

New users get `email_verified_at = null` by default. Verification uses signed URLs generated via `URL::temporarySignedRoute()`. The frontend URL is configured via `APP_FRONTEND_URL` in `.env`.

## Password Reset

Uses Laravel's built-in password broker. Reset link URLs are customized to point at `APP_FRONTEND_URL/reset-password?token=X&email=Y`. Password rules are configured in `AppServiceProvider` via `Password::defaults()` (min 8 chars, mixed case in production).
