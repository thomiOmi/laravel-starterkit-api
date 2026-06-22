---
name: laravel-patterns
description: Laravel architecture patterns, routing/controllers, Eloquent ORM, service layers, queues, events, caching, and API resources for production apps.
metadata:
  origin: ECC
---

# Laravel Development Patterns

Production-grade Laravel architecture patterns for scalable, maintainable applications.

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

Use modular layout with clear layer boundaries.

### Modular Layout

```
modules/{Module}/
├── Actions/            # Single-purpose use cases
├── Controllers/V1/     # HTTP layer (invokable)
├── Models/             # Eloquent models
├── Payloads/V1/        # DTOs
├── Requests/V1/        # Form request validation
├── Resources/          # API resources
├── Repositories/       # Read-only data access
├── Routes/V1.php       # Route definitions
├── Filters/            # Query filters
├── Database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
└── Tests/
```

### Actions

Use single-purpose actions for business logic.

```php
<?php

declare(strict_types=1);

namespace Modules\Blog\Actions;

use Modules\Blog\Models\Order;
use Modules\Blog\Payloads\V1\CreateOrderData;

final readonly class CreateOrderAction
{
    public function __construct(private OrderRepository $orders) {}

    public function handle(CreateOrderData $data): Order
    {
        return $this->orders->create($data);
    }
}
```

### Single-Action Controller

```php
<?php

declare(strict_types=1);

namespace Modules\Blog\Controllers\V1;

use Modules\Blog\Actions\CreateOrderAction;
use Modules\Blog\Requests\V1\StoreOrderRequest;
use Modules\Blog\Resources\OrderResource;

final readonly class OrdersController
{
    public function __construct(private CreateOrderAction $createOrder) {}

    public function __invoke(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->createOrder->handle($request->toDto());

        return response()->json([
            'status' => 201,
            'message' => 'Order created',
            'data' => OrderResource::make($order),
        ], 201);
    }
}
```

### Route Model Binding (Scoped)

```php
Route::scopeBindings()->group(function () {
    Route::get('/accounts/{account}/projects/{project}', [ProjectController::class, 'show']);
});
```

### Eloquent Model Patterns

```php
<?php

declare(strict_types=1);

namespace Modules\Blog\Models;

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

### Eager Loading to Avoid N+1

```php
$orders = Order::query()
    ->with(['customer', 'items.product'])
    ->latest()
    ->paginate(25);
```

### Transactions for Multi-Step Updates

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function (): void {
    $order->update(['status' => 'paid']);
    $order->items()->update(['paid_at' => now()]);
});
```

### Form Requests and Validation

```php
<?php

declare(strict_types=1);

namespace Modules\Blog\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Order::class) ?? false;
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

    public function toDto(): CreateOrderData
    {
        return new CreateOrderData(
            customerId: (int) $this->validated('customer_id'),
            items: $this->validated('items'),
        );
    }
}
```

### API Resources

```php
$projects = Project::query()->active()->paginate(25);

return response()->json([
    'status' => 200,
    'message' => 'Retrieved successfully',
    'data' => ProjectResource::collection($projects->items()),
    'meta' => [
        'page' => $projects->currentPage(),
        'per_page' => $projects->perPage(),
        'total' => $projects->total(),
    ],
]);
```

### Events, Jobs, and Queues

- Emit domain events for side effects (emails, analytics)
- Use queued jobs for slow work (reports, exports, webhooks)
- Prefer idempotent handlers with retries and backoff

### Caching

- Cache read-heavy endpoints and expensive queries
- Invalidate caches on model events (created/updated/deleted)
- Use tags when caching related data for easy invalidation
