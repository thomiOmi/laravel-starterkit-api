---
name: laravel-security
description: Laravel security best practices — authentication, authorization, Eloquent safety, CSRF, XSS prevention, API security, and secure deployment configurations.
metadata:
  origin: ECC
---

# Laravel Security Best Practices

Comprehensive security guidelines for Laravel applications to protect against common vulnerabilities.

## When to Activate

- Setting up Laravel authentication and authorization (Sanctum, Passport, Jetstream, Breeze)
- Implementing user roles, permissions, and policies
- Configuring production security settings and environment variables
- Reviewing Laravel applications for security vulnerabilities
- Deploying Laravel applications to production
- Writing secure Eloquent queries and migrations

## Production Configuration

### Essential Production Settings

```php
// config/app.php
'env' => env('APP_ENV', 'production'),
'debug' => (bool) env('APP_DEBUG', false), // CRITICAL: Never true in production
'key' => env('APP_KEY'), // Must be set: php artisan key:generate

// config/session.php
'secure' => env('SESSION_SECURE_COOKIE', true),
'http_only' => true,
'same_site' => 'lax',
```

### Environment File Security

```bash
# NEVER commit .env to version control
# .gitignore already includes .env by default
```

### HTTPS Enforcement

```php
// AppServiceProvider::boot() or middleware
if (app()->environment('production')) {
    URL::forceScheme('https');
    request()->server->set('HTTPS', 'on');
}
```

## Authentication

### Sanctum (API Token Authentication)

```php
// config/sanctum.php
'expiration' => 60 * 24, // Token expiration in minutes (null = never)
'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

// Issuing tokens with abilities
$token = $user->createToken('api-token', ['read', 'write'])->plainTextToken;

// Validate abilities on routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', function () {
        abort_unless(Auth::user()->tokenCan('read'), 403);
    })->middleware('abilities:read');

    Route::post('/orders', function () {
        abort_unless(Auth::user()->tokenCan('write'), 403);
    })->middleware('abilities:write');
});
```

### Password Security

```php
// config/hashing.php
'bcrypt' => [
    'rounds' => env('BCRYPT_ROUNDS', 12),
],

// Password validation
public function rules(): array
{
    return [
        'password' => [
            'required',
            'confirmed',
            Password::min(12)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),
        ],
    ];
}
```

### Session Management

```php
// config/session.php
'driver' => env('SESSION_DRIVER', 'database'),
'lifetime' => env('SESSION_LIFETIME', 120),
'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),
'encrypt' => env('SESSION_ENCRYPT', false),

// Regenerate session on login
$request->session()->regenerate(); // prevents session fixation
```

## Authorization

### Permission Checks (Spatie)

```php
// Check permission in FormRequest
public function authorize(): bool
{
    $user = $this->user();
    if ($user === null) {
        return false;
    }
    return $this->isMethod('POST')
        ? $user->can('role.create')
        : $user->can('role.edit');
}

// Check permission in Controller
public function __invoke(string $permission): SuccessResponse|ProblemResponse
{
    $user = auth()->user();
    if (! $user->can('permission.view')) {
        return new ProblemResponse(
            title: 'Forbidden',
            status: 403,
            detail: __('general.forbidden'),
        );
    }
    // ...
}
```

### Super-Admin Gate

```php
// App\Providers\AppServiceProvider
Gate::before(function (User $user, string $ability): ?bool {
    if ($user->hasRole('super-admin')) {
        return true;
    }
    return null;
});
```

## Eloquent Security

### Mass Assignment Protection

```php
// GOOD: Whitelist fillable attributes
final class User extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'phone', 'avatar',
    ];
    // NEVER add 'role', 'is_admin', 'is_verified' here
}

// GOOD: Use validated data only
$user = User::create($request->validated()); // Only validated fields

// BAD: $guarded = [] allows ALL columns to be mass-assigned
```

### SQL Injection Prevention

```php
// GOOD: Eloquent automatically parameterizes queries
User::where('email', $userInput)->first();
User::whereRaw('email = ?', [$userInput])->first();

// BAD: Raw string interpolation
DB::select("SELECT * FROM users WHERE email = '{$userInput}'"); // VULNERABLE!
User::whereRaw("email = '{$userInput}'")->first(); // VULNERABLE!
```

### Attribute Casting

```php
final class User extends Authenticatable
{
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // Auto-hashes on set
    ];
}
```

### Model Security

```php
final class User extends Authenticatable
{
    // Hide sensitive attributes from JSON/API responses
    protected $hidden = [
        'password',
        'remember_token',
    ];
}
```

## Input Validation

### Form Request Validation

```php
final class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Post::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,gif,webp',
                'max:2048',
            ],
        ];
    }
}
```

## API Security

### Rate Limiting

```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

// Route usage
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::apiResource('posts', PostController::class);
});
```

## Quick Security Checklist

| Check | Description |
|-------|-------------|
| `APP_DEBUG=false` | Never run with debug enabled in production |
| `APP_KEY` set | Always run `php artisan key:generate` |
| HTTPS enforced | Force HTTPS in production |
| `$fillable` whitelisted | Never use `$guarded = []` |
| Sanctum configured | API authentication with token abilities |
| Rate limiting applied | Throttle API and auth endpoints |
| Input validation | FormRequest with specific rules |
| `password` hashed | Use Laravel's built-in hashing (bcrypt/Argon2) |
| `.env` not committed | Verify `.gitignore` includes `.env` |

## Related Skills

- `laravel-patterns` — Laravel architecture, routing, Eloquent, and API patterns
