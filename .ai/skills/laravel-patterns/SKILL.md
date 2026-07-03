# Laravel Patterns Skill

## Core Philosophy

This project follows a Modular Monolith architecture. Modules are independent units of logic, data, and presentation.

### Data Flow

Controllers -> Actions -> Eloquent

Keep controllers thin. Put orchestration and single-purpose logic in actions.

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Actions;

use Modules\Sales\Models\Order;
use Modules\Sales\Payloads\CreateOrderPayload;

final class CreateOrderAction
{
    public function handle(CreateOrderPayload $data): Order
    {
        return Order::create($data->toArray());
    }
}

final class OrdersController extends Controller
{
    public function __construct(private CreateOrderAction $createOrder) {}

    public function __invoke(StoreOrderRequest $request): SuccessResponse
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

### Eager Loading to Avoid N+1

```php
$orders = Order::query()
    ->with(['customer', 'items.product'])
    ->latest()
    ->paginate(25);
```

### Form Requests and Validation

Keep validation in form requests and transform inputs to DTOs.

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
