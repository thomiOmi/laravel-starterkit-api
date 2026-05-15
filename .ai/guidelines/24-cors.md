# CORS Standards

Standalone APIs must handle Cross-Origin Resource Sharing (CORS) to allow requests from authorized external domains (e.g., a React/Vue frontend or a separate Admin dashboard).

## 1. Global Configuration

CORS is handled globally via Laravel's built-in `HandleCors` middleware and configured in `config/cors.php`.

## 2. Environment-Driven Origins

Allowed origins must be driven by environment variables. This ensures security and flexibility across different environments (Staging, Production) without changing the code.

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
# Production example
CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com

# Local example
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173
```

## 3. Anti-Patterns

- ❌ Do not use `allowed_origins => ['*']` in production.
- ❌ Do not hardcode origin URLs in the `config/cors.php` file.
- ❌ Do not include non-API paths in the CORS `paths` configuration if they don't need it.
