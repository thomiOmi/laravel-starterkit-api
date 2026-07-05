---
name: laravel-patterns
description: Laravel patterns and best practices
---

# Laravel Patterns Skill

## Core Philosophy

This project follows a Modular Monolith architecture. Modules are independent units of logic, data, and presentation.

## Project Structure

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

│   ├── Repositories/    # Read-only data access (optional, avoid in IAM)

│   ├── Requests/        # Form request validation

│   ├── Resources/       # API resources

│   ├── Routes/          # V1.php, V2.php

│   └── Tests/           # Feature and unit tests

└── ...
app/                     # Shared application code

├── Concerns/            # Traits and shared logic

├── Contracts/           # Interfaces for DI

├── Http/
│   ├── Controllers/     # Base controller

│   ├── Middleware/      # ForceJsonResponse, etc.

│   └── Responses/       # SuccessResponse, ProblemResponse

├── Providers/           # AppServiceProvider

├── Models/              # Shared Eloquent models

├── Notifications/       # Shared notifications

├── Supports/            # Shared helpers and utilities

└── ...
config/
database/
├── factories/           # Shared factories

├── migrations/          # Shared migrations

└── seeders/             # Shared seeders

routes/
├── api.php              # Module route loader

└── console.php
tests/                   # Shared tests / global test helpers

├── Architecture/        # Architecture tests (e.g., modular structure)

├── Feature/
└── Unit/
```

### Data Flow

Controllers -> Actions -> Eloquent (or Repositories if applicable)

Keep controllers thin. Put orchestration and single-purpose logic in actions.

```php
<?php

declare(strict_types=1);

namespace Modules\Sales\Actions;

use Modules\Sales\Models\Order;
use Modules\Sales\Payloads\V1\CreateOrderPayload;

final readonly class CreateOrderAction
{
    public function handle(CreateOrderPayload $data): Order
    {
        return Order::create($data->toArray());
    }
}

final readonly class OrdersController
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

This project uses single-action controllers (`__invoke`) with explicit route definitions.

```php
<?php

declare(strict_types=1);

namespace Modules\User\Routes;

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\V1\IndexController;
use Modules\User\Controllers\V1\CreateController;

Route::prefix('users')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/', IndexController::class)->name('users.index');
    Route::post('/', CreateController::class)->name('users.create');
});
```

### Form Requests and Validation

Keep validation in form requests and transform inputs to Payloads (DTOs).

### API Resources

Keep API responses consistent with resources and SuccessResponse.

```php
<?php

declare(strict_types=1);

use App\Http\Responses\SuccessResponse;

$projects = Project::query()->paginate(25);

return new SuccessResponse(
    title: 'Projects Retrieved',
    detail: 'List of active projects.',
    data: ProjectResource::collection($projects),
);
```
