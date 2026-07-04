---
name: laravel-security
description: API Security standards for Laravel 13. Includes Sanctum per-device auth, Spatie permissions (RBAC), and secure file handling with Signed URLs.
license: MIT
metadata:
  version: "2.2.0"
---

# Laravel API Security Standards

Comprehensive security patterns to protect your application and data.

## 1. Authentication (Sanctum)
We use Laravel Sanctum with a focus on per-device token management.

### Issuing Secure Tokens
```php
public function login(LoginRequest $request): SuccessResponse
{
    $user = $this->authenticate($request);

    // Support device-specific names for better UX/Security
    $token = $user->createToken(
        name: $request->input('device_name', 'Default Device'),
        abilities: ['*'],
        expiresAt: now()->addMonths(6)
    );

    return new SuccessResponse(['token' => $token->plainTextToken]);
}
```

## 2. Authorization (RBAC)
Using Spatie Laravel Permission with a strict "Check Abilities, not Roles" approach.

### Example Policy
```php
final readonly class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        // Admin can do anything
        if ($user->hasRole('admin')) return true;

        // Owner check + specific permission
        return $user->id === $post->user_id && $user->can('posts.update');
    }
}
```

## 3. Secure File Access (Signed URLs)
Never expose direct storage links for private files.

```php
use Illuminate\Support\Facades\Storage;

final readonly class DownloadAction
{
    public function handle(Document $doc): string
    {
        // Generates a link that expires in 15 minutes
        return Storage::disk('s3')->temporaryUrl(
            $doc->path,
            now()->addMinutes(15)
        );
    }
}
```

## Security Checklist
1. **APP_DEBUG:** Must be `false` in production to prevent leaking sensitive info via RFC 9457 details.
2. **Password Hashing:** Ensure `hashed` cast is used in the User model.
3. **Mass Assignment:** Use `$fillable` or `#[Fillable]` and always validate via `FormRequest`.

## Constraints
- **MUST** use `ProblemResponse` for 403/401 errors.
- **MUST** use `auth:sanctum` middleware for all protected routes.
- **MUST** use Signed URLs for any data that is not explicitly public.
