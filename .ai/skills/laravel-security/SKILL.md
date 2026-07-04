---
name: laravel-security
description: Security standards for Laravel 13 APIs. Handles Sanctum per-device auth, RBAC with Spatie, secure file handling, and mass assignment protection.
license: MIT
metadata:
  version: "2.3.0"
---

# Laravel API Security Standards

Comprehensive security patterns for industrial-grade Laravel applications.

## Gotchas
- **Broad CORS:** Never set `allowed_origins` to `*` in production.
- **Leaked Tokens:** Ensure Sanctum tokens are only displayed once upon creation.
- **Hardcoded Roles:** Never check for `->hasRole('admin')` directly in business logic. Use `->can('update-posts')` and assign permissions to roles.

## 1. Authentication (Sanctum Per-Device)
Allow users to manage individual device sessions.

### Implementation: Secure Login
```php
final readonly class LoginAction
{
    public function handle(LoginPayload $payload): string
    {
        $user = User::where('email', $payload->email)->first();

        if (! $user || ! Hash::check($payload->password, $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        // Include device/agent info for security auditing
        return $user->createToken(
            name: $payload->deviceName ?? 'Unknown Device',
            abilities: ['*'],
            expiresAt: now()->addWeeks(4)
        )->plainTextToken;
    }
}
```

## 2. Authorization (Abilities Over Roles)
Always authorize based on specific abilities/permissions.

### Template: Model Policy
```php
final readonly class PostPolicy
{
    /**
     * Use Gate::before in AppServiceProvider for super-admin bypass.
     */
    public function update(User $user, Post $post): bool
    {
        // 1. Check specific permission
        if (! $user->can('posts.update')) {
            return false;
        }

        // 2. Check ownership or other domain constraints
        return $user->id === $post->user_id;
    }
}
```

## 3. Secure Media (Signed URLs)
Ensure private media is never accessed via direct public paths.

```php
use Illuminate\Support\Facades\Storage;

final readonly class GetDocumentUrlAction
{
    public function handle(Document $doc): string
    {
        // 1. Authorize access
        // 2. Generate temporary link
        return Storage::disk('s3')->temporaryUrl(
            $doc->path,
            now()->addMinutes(15),
            ['ResponseContentDisposition' => 'attachment']
        );
    }
}
```

## 4. Verification Checkpoints
- [ ] Are all routes protected by `auth:sanctum`?
- [ ] Is `APP_DEBUG=false` in your `.env.production`?
- [ ] Do all models use `$fillable` or `#[Fillable]`?
- [ ] Are sensitive fields (password, tokens) in `$hidden`?

## Constraints
- **MUST** use `ProblemResponse` for unauthorized (401) or forbidden (403) access.
- **MUST** read `references/security-audit.md` before implementing custom auth drivers.
