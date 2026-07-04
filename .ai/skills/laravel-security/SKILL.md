---
name: laravel-security
description: API Security standards for Laravel 13. Includes Sanctum per-device auth, Spatie permissions, RFC 9457 error handling, and secure file handling with Signed URLs.
license: MIT
metadata:
  version: "2.0.0"
---

# Laravel Security Best Practices

Guidelines for securing Laravel 13 Backend APIs.

## Core Security Rules

### 1. Authentication (Sanctum)
- Always use `auth:sanctum` middleware.
- Support per-device token naming.
- **Verification:** Run `php artisan route:list` to ensure routes are protected.

### 2. Authorization (Spatie + Policies)
- Prefer `Policy` classes over `Gate` definitions for domain models.
- Register `Gate::before` for Super Admin in `AppServiceProvider`.
- **Constraint:** Never hardcode role IDs. Use Enums.

### 3. Error Exposure (RFC 9457)
- Use `ProblemResponse` to avoid leaking stack traces.
- Ensure `APP_DEBUG=false` in production.

### 4. Secure File Access
- Use `Storage::temporaryUrl()` for private files.
- Never expose `/storage/` paths for sensitive data.

## Code Templates

### Secure Controller
```php
final readonly class DocumentController extends Controller
{
    public function show(Document $document)
    {
        $this->authorize('view', $document);
        return new SuccessResponse([
            'download_url' => Storage::temporaryUrl($document->path, now()->addMinutes(10))
        ]);
    }
}
```

## Security Verification Loop
1. Check `composer audit` for vulnerable packages.
2. Verify `X-Frame-Options` and `X-Content-Type-Options` headers.
3. Check `config/cors.php` for strict origin whitelisting.
