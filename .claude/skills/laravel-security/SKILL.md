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

// Verify APP_KEY is set at boot
// bootstrap/app.php or a service provider
if (empty(config('app.key'))) {
    throw new RuntimeException('APP_KEY is not set. Run: php artisan key:generate');
}
```

### Environment File Security

```bash

# NEVER commit .env to version control

# .gitignore already includes .env by default

# Use .env.example with placeholders instead

DB_PASSWORD=
APP_KEY=
SANCTUM_TOKEN_PREFIX=

# Validate required variables at boot

// In AppServiceProvider::boot()
$requiredKeys = ['app.key', 'database.connections.mysql.database', 'database.connections.mysql.username'];
foreach ($requiredKeys as $key) {
    if (empty(config($key))) {
        throw new RuntimeException("Missing required config key: {$key}");
    }
}
```

### HTTPS Enforcement

```php
// AppServiceProvider::boot() or middleware
if (app()->environment('production')) {
    URL::forceScheme('https');
    request()->server->set('HTTPS', 'on');
}

// config/app.php for trusted proxies (load balancers)
// Use specific IP ranges — * trusts all, allowing X-Forwarded-* spoofing
// AWS: '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'
'trusted_proxies' => ['10.0.0.0/8', '172.16.0.0/12'],

// Force HTTPS in production via middleware
// app/Http/Middleware/ForceHttps.php
public function handle($request, Closure $next)
{
    if (!$request->secure() && app()->environment('production')) {
        return redirect()->secure($request->getRequestUri());
    }
    return $next($request);
}
```

## Authentication

### Sanctum (API Token Authentication)

```php
// config/sanctum.php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    env('APP_URL') ? ',' . parse_url(env('APP_URL'), PHP_URL_HOST) : ''
)));

'expiration' => 60 * 24, // Token expiration in minutes (null = never)
'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

// Issuing tokens with abilities
$token = $user->createToken('api-token', ['read', 'write'])->plainTextToken;

// Validate abilities on routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', function () {
        // User must have 'read' ability
        abort_unless(Auth::user()->tokenCan('read'), 403);
        // ...
    })->middleware('abilities:read');

    Route::post('/orders', function () {
        // User must have 'write' ability
        abort_unless(Auth::user()->tokenCan('write'), 403);
        // ...
    })->middleware('abilities:write');
});
```

### Password Security

```php
// config/hashing.php
// Default is bcrypt. Argon2id is stronger.
'bcrypt' => [
    'rounds' => env('BCRYPT_ROUNDS', 12), // Increase for stronger hashing
],

'argon' => [
    'memory' => 65536,
    'threads' => 4,
    'time' => 4,
],

// Password validation in RegisterRequest
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
                ->uncompromised(), // Checks haveibeenpwned
        ],
    ];
}

// Rate limit login attempts
// App\Http\Controllers\Auth\AuthenticatedSessionController
protected function authenticated(Request $request, $user)
{
    if ($user->wasRecentlyLockedOut()) {
        // Notify user of suspicious login
        $user->notify(new SuspiciousLoginNotification($request->ip()));
    }
}
```

### Session Management (API-only — Sanctum token-based)

This project is **API-only** with Sanctum token auth — no session-based auth. Sessions are not used. Token lifecycle is managed via Sanctum:

```php
// Token creation
$token = $user->createToken('device-name', ['read', 'write'])->plainTextToken;

// Token revocation (logout)
$request->user()->currentAccessToken()->delete();

// Revoke all tokens (logout other devices)
$request->user()->tokens()->delete();
```

## Authorization (Spatie laravel-permission)

This project uses `spatie/laravel-permission` for role/permission authorization.

### Defining Permissions in a Service Provider

```php
<?php

declare(strict_types=1);

namespace Modules\Role\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;

final class RoleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Permission::create(['name' => 'users.create']);
        Permission::create(['name' => 'users.read']);
        Permission::create(['name' => 'users.update']);
        Permission::create(['name' => 'users.delete']);
    }
}
```

### Assigning Roles and Permissions

```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Create role and assign permissions
$role = Role::create(['name' => 'admin']);
$role->givePermissionTo('users.create', 'users.read', 'users.update', 'users.delete');

// Assign role to a user
$user->assignRole('admin');

// Give direct permission to a user
$user->givePermissionTo('users.create');

// Check permissions
$user->hasPermissionTo('users.create'); // true/false
```

### Controller Authorization with Spatie

```php
<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Modules\User\Actions\UpdateUserAction;
use Modules\User\Requests\UserRequest;

final class UpdateController extends Controller
{
    public function __invoke(UserRequest $request, string $user): SuccessResponse|ProblemResponse
    {
        if (!$request->user()?->can('users.update')) {
            return new ProblemResponse(403, 'Forbidden', 'You do not have permission to update users.');
        }

        // ... handle update

        return new SuccessResponse(
            title: 'User Updated',
            detail: 'The user has been updated successfully.',
        );
    }
}
```

### FormRequest Authorization

```php
<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
        ];
    }
}
```

### Route Middleware

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\V1\IndexController;
use Modules\User\Controllers\V1\CreateController;
use Modules\User\Controllers\V1\UpdateController;
use Modules\User\Controllers\V1\DeleteController;

Route::prefix('users')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', IndexController::class)->middleware('permission:users.read');
    Route::post('/', CreateController::class)->middleware('permission:users.create');
    Route::put('/{user}', UpdateController::class)->middleware('permission:users.update');
    Route::delete('/{user}', DeleteController::class)->middleware('permission:users.delete');
});
```

## Eloquent Security

### Mass Assignment Protection

```php
// BAD: $guarded = [] allows ALL columns to be mass-assigned
// NEVER use $guarded = [] in production

// GOOD: Whitelist fillable attributes
final class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
    ];
    // NEVER add 'role', 'is_admin', 'is_verified' here
}

// GOOD: Explicitly control which fields can be filled in requests
public function store(StoreUserRequest $request): RedirectResponse
{
    $user = User::create($request->safe()->only([
        'name', 'email', 'phone', 'avatar'
    ]));
    // $request->safe() uses validated data only
    // $request->only() is NOT safe on its own without validation rules
}

// BAD: Creating a user with request data directly
User::create($request->all()); // VULNERABLE to mass assignment!

// BETTER: Use DTOs for creation
$user = User::create($request->validated()); // Only validated fields
```

### SQL Injection Prevention

```php
// GOOD: Eloquent automatically parameterizes queries
User::where('email', $userInput)->first();
User::whereRaw('email = ?', [$userInput])->first();

// GOOD: Query Builder also parameterizes
DB::table('users')->where('email', $userInput)->first();
DB::select('SELECT * FROM users WHERE email = ?', [$userInput]);

// BAD: Raw string interpolation
DB::select("SELECT * FROM users WHERE email = '{$userInput}'"); // VULNERABLE!
User::whereRaw("email = '{$userInput}'")->first(); // VULNERABLE!

// BAD: whereRaw/orderByRaw with unescaped input
User::orderByRaw($userInput); // VULNERABLE!
User::groupByRaw($userInput); // VULNERABLE!

// BAD: DB::statement with concatenation
DB::statement("INSERT INTO users (email) VALUES ('{$userInput}')"); // VULNERABLE!
```

### Attribute Casting

```php
final class User extends Authenticatable
{
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean', // Cast to boolean prevents string injection
        'settings' => 'array', // Automatically json_encode/json_decode
        'metadata' => 'encrypted:array', // Laravel 11+ encrypted casting
        'password' => 'hashed', // Laravel 10+ auto-hashes on set
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
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    // Append only safe computed attributes
    protected $appends = ['full_name']; // safe
    // NEVER append sensitive computed data
}

final class Post extends Model
{
    // Global scope to filter soft deleted records
    use SoftDeletes;

    // Prevent N+1 by restricting lazy loading (optional strict mode)
    // AppServiceProvider::boot()
    // Model::preventLazyLoading(!app()->isProduction());
}
```

## CSRF Protection (Not Applicable)

This project is **API-only** using Sanctum token-based auth. CSRF tokens are not required for stateless API authentication. CSRF protection only applies to session-based web routes (stateful Sanctum).

## XSS Prevention (API-only)

For API responses, ensure JSON output is properly encoded. Laravel's `JsonResponse` handles this automatically. User-generated content stored in the database is returned as raw strings — the frontend is responsible for output escaping.

### HTTP Security Headers for API Responses

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
```

## Input Validation

### Form Request Validation

```php
<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
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

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered.',
            'password.uncompromised' => 'This password has appeared in a data breach. Please choose a different password.',
        ];
    }
}
```

### Custom Validation Rules

```php
<?php

declare(strict_types=1);

namespace Modules\User\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^()_\-+=])[A-Za-z\d@$!%*?&#^()_\-+=]{12,}$/', (string) $value)) {
            $fail('The :attribute must be at least 12 characters with uppercase, lowercase, number, and symbol.');
        }
    }
}
```

## API Security

### Rate Limiting

```php
// App/Providers/RouteServiceProvider
protected function configureRateLimiting(): void
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    RateLimiter::for('auth', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip())
            ->response(function () {
                return response()->json([
                    'status' => 429,
                    'title' => 'Too Many Requests',
                    'detail' => 'Too many login attempts. Try again in 1 minute.',
                ], 429);
            });
    });
}

// Route usage
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::apiResource('posts', PostController::class);
});

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:auth');
```

### API Authentication (Sanctum)

```php
<?php

declare(strict_types=1);

// config/sanctum.php
'expiration' => 60 * 24, // Tokens expire after 24 hours

// Issuing token
$token = $user->createToken('device-name', ['users:read', 'users:write'])->plainTextToken;

// Middleware
Route::prefix('users')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [UserController::class, 'index'])->middleware('permission:users.read');
    Route::post('/', [UserController::class, 'store'])->middleware('permission:users.create');
});
```

### CORS Configuration

```php
<?php

declare(strict_types=1);

// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '')),
    'allowed_headers' => ['*'],
    'exposed_headers' => ['X-Total-Count', 'X-Pagination-Page'],
    'max_age' => 0,
    'supports_credentials' => false, // Token-based, no cookies needed
];

// NEVER: Allow all origins in production unless absolutely necessary
// 'allowed_origins' => ['*'], // Only for truly public APIs
```

## File Upload Security

### Validation

```php
public function rules(): array
{
    return [
        'document' => [
            'required',
            'file',
            'mimes:pdf,doc,docx,xls,xlsx', // Whitelist specific MIME types
            'max:10240', // 10MB
            'extensions:pdf,doc,docx,xls,xlsx', // Verify extension matches MIME
        ],
        'avatar' => [
            'nullable',
            'image', // Ensures it's a valid image
            'mimes:jpg,jpeg,png,webp',
            'max:2048',
            'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
        ],
    ];
}
```

### Secure Storage

```php
// Store files outside public directory
$path = $request->file('document')->store('documents', 'local');
// Never use 'public' disk for sensitive documents

// Use signed URLs for temporary file access
use Illuminate\Support\Facades\Storage;

public function download(Request $request, string $path)
{
    // Generate temporary signed URL (expires in 15 minutes)
    $url = Storage::temporaryUrl($path, now()->addMinutes(15));

    // Validate user has permission
    $this->authorize('download', $path);

    return redirect($url);
}

// Storage configuration for cloud with encryption
// config/filesystems.php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'throw' => false,
    'server_side_encryption' => 'AES256', // Encrypt at rest
],
```

## Dependencies and Secrets

### Composer Security

```bash

# Always audit dependencies in CI

composer audit

# Pin major versions in composer.json

"laravel/framework": "^11.0",
"spatie/laravel-permission": "^6.0"

# Check for abandoned packages

composer why-not

# Keep lock file in version control (it pins exact versions)

# Run `composer update` deliberately, never in CI/CD

```

### Secret Management

```bash

# .env file (NEVER commit)

# .gitignore includes .env by default

APP_KEY=base64:abc123...
DB_PASSWORD=secure_password
STRIPE_KEY=sk_live_...
SANCTUM_TOKEN_PREFIX=myapp_

# For production: Use a secret manager

# Deploy with: env $(aws secretsmanager get-secret-value --secret-id prod/db | jq ...) php artisan serve

# Validate secrets at boot (AppServiceProvider::boot)

$secrets = ['services.stripe.key', 'services.stripe.webhook_secret'];
foreach ($secrets as $key) {
    if (empty(config($key))) {
        Log::critical("Missing secret: {$key}");
    }
}
```

## Queue Security

```php
<?php

declare(strict_types=1);

namespace Modules\Payments\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $paymentIntentId,
    ) {}
}
```

## Logging Security Events

```php
// config/logging.php
'channels' => [
    'security' => [
        'driver' => 'single',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
    ],
],

// Audit log helper
final class SecurityLogger
{
    public static function log(string $event, array $context = []): void
    {
        Log::channel('security')->warning($event, array_merge([
            'user_id' => Auth::id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }
}

// Usage
SecurityLogger::log('failed_login_attempt', ['email' => $email]);
SecurityLogger::log('password_change');
SecurityLogger::log('role_change', ['target_user' => $targetId, 'new_role' => 'admin']);
SecurityLogger::log('suspicious_activity', ['reason' => 'multiple_attempts_from_different_ips']);
```

## Quick Security Checklist

| Check | Description |
|-------|-------------|
| `APP_DEBUG=false` | Never run with debug enabled in production |
| `APP_KEY` set | Always run `php artisan key:generate` |
| HTTPS enforced | Force HTTPS in production via middleware or proxy |
| `$fillable` whitelisted | Never use `$guarded = []` |
| Sanctum configured | API authentication with token abilities/scopes |
| Spatie permissions set | `$user->can('permission.name')` checks in controllers/requests |
| Rate limiting applied | Throttle API and auth endpoints |
| Input validation | FormRequest with specific rules, never `$request->all()` |
| File upload restrictions | Validate MIME types, size, dimensions |
| `composer audit` in CI | Check dependencies for known vulnerabilities |
| Password hashing | Use Laravel's built-in hashing (bcrypt/Argon2) |
| Security headers middleware | X-Frame-Options, X-Content-Type-Options |
| Logged security events | Audit log for auth failures, role changes, suspicious activity |
| `.env` not committed | Verify `.gitignore` includes `.env` |
| `declare(strict_types=1)` | All PHP files must declare strict types |

## Related Skills

- `laravel-patterns` — Laravel architecture, routing, Eloquent, and API patterns
- `laravel-specialist` — Laravel models, controllers, queues, and Pest testing
- `pest-testing` — Pest PHP testing framework in this project
- `laravel-permission-development` — Spatie role/permission patterns
