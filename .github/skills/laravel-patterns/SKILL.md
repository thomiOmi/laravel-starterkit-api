---
name: laravel-patterns
description: Laravel architecture patterns, routing/controllers, Eloquent ORM, service layers, queues, events, caching, and API resources for production apps.
metadata:
  origin: ECC
---

# Laravel Development Patterns

Production-grade Laravel architecture patterns for scalable, maintainable applications.

## Reference Guide

| Topic | Reference | Load When |
|-------|-----------|-----------|
| Modular Architecture | `references/modular-architecture.md` | Module structure, data flow, service providers |

## When to Use

- Building Laravel web applications or APIs
- Structuring controllers, services, and domain logic
- Working with Eloquent models and relationships
- Designing APIs with resources and pagination
- Adding queues, events, caching, and background jobs

## How It Works

- Structure the app around clear boundaries (controllers -> services/actions -> models).
- Use explicit bindings and scoped bindings to keep routing predictable; still enforce authorization for access control.
- Favor typed models, casts, and scopes to keep domain logic consistent.
- Keep IO-heavy work in queues and cache expensive reads.
- Centralize config in `config/*` and keep environments explicit.

## Examples

### Project Structure

Use a conventional Laravel layout with clear layer boundaries (HTTP, services/actions, models).

### Recommended Layout (Modular DDD)

```
modules/
├── {Module}/
│   ├── Actions/         # Single-purpose use cases

│   ├── Controllers/     # V1/, V2/ for API versioning

│   ├── Database/
│   │   ├── factories/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── Events/
│   ├── Filters/         # Query/filter objects

│   ├── Jobs/
│   ├── Models/
│   ├── Payloads/        # DTOs with PHP 8.4 property hooks

│   ├── Providers/       # Service providers

│   ├── Repositories/
│   ├── Requests/        # Form request validation

│   ├── Resources/       # API resources

│   ├── Routes/          # V1.php, V2.php

│   └── Tests/           # Feature tests

├── Auth/
├── Role/
└── User/
app/
├── Http/
│   ├── Controllers/     # Base controller

│   ├── Middleware/      # ForceJsonResponse, etc.

│   └── Responses/       # SuccessResponse, ProblemResponse

└── Providers/           # AppServiceProvider

config/
database/
├── factories/           # Shared factories

├── migrations/          # Shared migrations

└── seeders/             # Shared seeders (RoleSeeder)

routes/
├── api.php              # Module route loader

└── console.php
```

### Controllers -> Services -> Actions

Keep controllers thin. Put orchestration in services and single-purpose logic in actions.

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Actions;

use Modules\Sales\Models\Order;
use Modules\Sales\Payloads\CreateOrderPayload;
use Modules\Sales\Repositories\OrderRepository;

final class CreateOrderAction
{
    public function __construct(private OrderRepository $orders) {}

    public function handle(CreateOrderPayload $data): Order
    {
        return $this->orders->create($data);
    }
}

final class OrdersController extends Controller
{
    public function __construct(private CreateOrderAction $createOrder) {}

    public function store(StoreOrderRequest $request): SuccessResponse
    {
        $order = $this->createOrder->handle($request->toPayload());

        return new SuccessResponse(
            title: 'Order Created',
            detail: 'The order has been created successfully.',
            data: OrderResource::make($order),
            status: 201,
        );
    }
}
```

### Routing and Controllers (Single-Action Pattern)

This project uses single-action controllers (`__invoke`) with explicit route definitions — not resource controllers.

```php
<?php

declare(strict_types=1);

namespace Modules\User\Routes;

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\V1\IndexController;
use Modules\User\Controllers\V1\CreateController;
use Modules\User\Controllers\V1\ShowController;
use Modules\User\Controllers\V1\UpdateController;
use Modules\User\Controllers\V1\DeleteController;

Route::prefix('users')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/', IndexController::class)->name('users.index');
    Route::post('/', CreateController::class)->name('users.create');
    Route::get('/{user}', ShowController::class)->name('users.show');
    Route::put('/{user}', UpdateController::class)->name('users.update');
    Route::delete('/{user}', DeleteController::class)->name('users.delete');
});
```

### Route Model Binding (Scoped)

Use scoped bindings to prevent cross-tenant access.

```php
<?php

declare(strict_types=1);

Route::scopeBindings()->group(function () {
    Route::get('/accounts/{account}/projects/{project}', [ProjectController::class, 'show']);
});
```

### Nested Routes and Binding Names

- Keep prefixes and paths consistent to avoid double nesting.
- Prefer scoped bindings when nesting to enforce parent-child relationships.
- Routes are defined in `modules/{Module}/Routes/V1.php` and loaded via a service provider.

```php
<?php

declare(strict_types=1);

use Modules\Cms\Controllers\V1\ConversationController;
use Modules\Cms\Controllers\V1\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('conversations')->group(function () {
    Route::post('/', [ConversationController::class, 'store'])->name('conversations.store');

    Route::scopeBindings()->group(function () {
        Route::get('/{conversation}', [ConversationController::class, 'show'])
            ->name('conversations.show');

        Route::post('/{conversation}/messages', [MessageController::class, 'store'])
            ->name('conversation-messages.store');
    });
});
```

### Service Container Bindings

Bind interfaces to implementations in a service provider for clear dependency wiring.

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Providers;

use Modules\Sales\Repositories\EloquentOrderRepository;
use Modules\Sales\Repositories\OrderRepository;
use Illuminate\Support\ServiceProvider;

final class SalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);
    }
}
```

### Eloquent Model Patterns

### Model Configuration

```php
<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'owner_id', 'status'];

    protected $casts = [
        'status' => ProjectStatus::class,
        'archived_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }
}
```

### Custom Casts and Value Objects

Use enums or value objects for strict typing.

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Casts\Attribute;

protected $casts = [
    'status' => ProjectStatus::class,
];

protected function budgetCents(): Attribute
{
    return Attribute::make(
        get: fn (int $value) => Money::fromCents($value),
        set: fn (Money $money) => $money->toCents(),
    );
}
```

### Eager Loading to Avoid N+1

```php
$orders = Order::query()
    ->with(['customer', 'items.product'])
    ->latest()
    ->paginate(25);
```

### Query Objects for Complex Filters

```php
<?php

declare(strict_types=1);

namespace Modules\Cms\Filters;

use Illuminate\Database\Eloquent\Builder;

final class ProjectFilter
{
    public function __construct(private Builder $query) {}

    public function ownedBy(int $userId): self
    {
        $query = clone $this->query;

        return new self($query->where('owner_id', $userId));
    }

    public function active(): self
    {
        $query = clone $this->query;

        return new self($query->whereNull('archived_at'));
    }

    public function builder(): Builder
    {
        return $this->query;
    }
}
```

### Global Scopes and Soft Deletes

Use global scopes for default filtering and `SoftDeletes` for recoverable records.
Use either a global scope or a named scope for the same filter, not both, unless you intend layered behavior.

```php
<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

final class Project extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope('active', function (Builder $builder): void {
            $builder->whereNull('archived_at');
        });
    }
}
```

### Query Scopes for Reusable Filters

```php
<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Builder;

final class Project extends Model
{
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('owner_id', $userId);
    }
}

// Usage
$projects = Project::ownedBy($user->id)->get();
```

### Transactions for Multi-Step Updates

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

DB::transaction(function (): void {
    $order->update(['status' => 'paid']);
    $order->items()->update(['paid_at' => now()]);
});
```

### Migrations

### Naming Convention

- File names use timestamps: `YYYY_MM_DD_HHMMSS_create_users_table.php`
- Migrations use anonymous classes (no named class); the filename communicates intent
- Table names are `snake_case` and plural by default

### Example Migration

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->index();
            $table->unsignedInteger('total_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
```

### Form Requests and Validation

Keep validation in form requests and transform inputs to DTOs.

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Sales\Models\Order;
use Modules\Sales\Payloads\CreateOrderPayload;

final class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('orders.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toPayload(): CreateOrderPayload
    {
        return new CreateOrderPayload(
            customerId: (int) $this->validated('customer_id'),
            items: $this->validated('items'),
        );
    }
}
```

### API Resources

Keep API responses consistent with resources and pagination.

```php
<?php

declare(strict_types=1);

use App\Http\Responses\SuccessResponse;

$projects = Project::query()->active()->paginate(25);

return new SuccessResponse(
    title: 'Projects Retrieved',
    detail: 'List of active projects.',
    data: ProjectResource::collection($projects),
);
```

### Events, Jobs, and Queues

- Emit domain events for side effects (emails, analytics)
- Use queued jobs for slow work (reports, exports, webhooks)
- Prefer idempotent handlers with retries and backoff

### Caching

- Cache read-heavy endpoints and expensive queries
- Invalidate caches on model events (created/updated/deleted)
- Use tags when caching related data for easy invalidation

### Configuration and Environments

- Keep secrets in `.env` and config in `config/*.php`
- Use per-environment config overrides and `config:cache` in production
