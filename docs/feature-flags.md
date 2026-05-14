# Feature Flags (Laravel Pennant)

This project uses **Laravel Pennant** to manage features dynamically. This allows you to enable or disable features for specific users without changing the code.

## 1. Defining Features

Features are defined within `app/Providers/AppServiceProvider.php` (or a dedicated provider if there are many features).

```php
use Laravel\Pennant\Feature;
use Modules\User\Models\User;

public function boot(): void
{
    Feature::define('new-dashboard', function (User $user) {
        return $user->hasRole('admin');
    });
}
```

## 2. Checking Features

### In Controller or Action
```php
use Laravel\Pennant\Feature;

if (Feature::active('new-dashboard')) {
    // Show new dashboard
}
```

### In Middleware (Optional)
You can create a custom middleware to restrict route access based on features.

## 3. Storage
By default, feature states are stored in the database using the `features` table. This allows runtime status changes without redeploying.

## 4. Enterprise Ready Benefits
- **Beta Testing:** Enable new features only for a small group of users.
- **Gradual Rollout:** Launch features progressively.
