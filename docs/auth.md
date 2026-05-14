# Authentication & Device Management

The authentication system in this project uses a clean custom implementation with **Laravel Sanctum** for API token management.

## 1. Available Features
All authentication endpoints are located under the `/api/v1/auth` prefix.

- **Registration:** `POST /register` (Triggers `UserRegistered` Event).
- **Login:** `POST /login`
- **Logout:** `POST /logout`
- **Password Reset:** `POST /forgot-password` and `POST /reset-password`
- **Email Verification:** `POST /email/verification-notification` and `GET /email/verify/{id}/{hash}`
- **Social Login:** Supports Google and GitHub via Laravel Socialite.

### Social Login Flow
The system supports third-party authentication (Google & GitHub).

1. **Redirect**: Frontend directs the user to `GET /api/v1/auth/social/{provider}/redirect`.
2. **Callback**: After the user logs in with the provider, the provider redirects back to the callback URL, which processes user data at `GET /api/v1/auth/social/{provider}/callback`.
3. **Success**: The server creates/matches the user based on email and returns a Sanctum token.

## 2. Standard JSON Response
All authentication endpoints return consistent JSON responses using the `ApiResponser` trait:

```json
{
    "status": "success",
    "message": "Login successful.",
    "data": {
        "user": { ... },
        "access_token": "...",
        "token_type": "Bearer"
    }
}
```

## 3. Device Management (Multi-Device)
Every login generates a new `PersonalAccessToken` that records device information.

- **Device List:** `GET /auth/devices`
- **Logout Specific Device:** `DELETE /auth/devices/{id}`
- **Logout Other Devices:** `POST /auth/devices/logout-others`

## 4. Event-Driven Flow
The authentication process utilizes Laravel Events for decoupling:
- **UserRegistered**: Triggered after successful registration. The `SendEmailVerificationNotification` listener handles sending emails asynchronously via a Queue.

## 5. Localization (i18n)
Authentication error and success messages support multiple languages via the `Accept-Language` header (id/en).
