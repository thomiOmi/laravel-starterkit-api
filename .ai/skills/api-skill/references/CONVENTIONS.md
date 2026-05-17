# Conventions Reference

This document covers folder structure, naming conventions, and complete worked examples for the API skill.

---

## 1. Folder Structure

This project follows a strict **Domain-Driven Modular Architecture**. All domain logic must reside within versioned modules. Note: Always use uppercase `V1`.

```text
modules/
  {Module}/
    Actions/            # Business logic classes
    Controllers/
      V1/               # Versioned single-action controllers
    Payloads/
      V1/               # Versioned Data Transfer Objects
    Models/             # Eloquent models
    Requests/
      V1/               # Versioned form requests
    Resources/          # Eloquent resources
    Routes/
      V1.php            # Version-specific route definitions (Uppercase)
    Filters/            # Query filters (extending BaseFilter)
    Events/             # Domain events for cross-module communication
    Listeners/          # Event listeners
    Database/           # Migrations, Factories, Seeders
    Tests/
      Feature/
        V1/             # Versioned feature tests
```

---

## 2. Naming Conventions

| Layer | Convention | Example |
|---|---|---|
| **Controller** | `V{Version}\{Action}Controller` | `V1\StoreController` |
| **Action** | `{Action}{Resource}Action` | `StoreUserAction` |
| **Payload** | `V{Version}\{Action}{Resource}Payload` | `V1\StoreUserPayload` |
| **Form Request** | `V{Version}\{Action}{Resource}Request` | `V1\StoreUserRequest` |
| **API Resource** | `{Resource}Resource` | `UserResource` |
| **Job** | `{Action}{Resource}Job` | `DeletePostJob` |
| **Filter** | `{Resource}Filter` | `UserFilter` |
| **Policy** | `{Resource}Policy` | `PostPolicy` |
| **Route Name** | `{module_name}.{action}` | `users.store` |
| **Test File** | `V{Version}\{Action}Test.php` | `V1\StoreTest.php` |

---

## 3. Implementation — Action Composition (Orchestrator)

For complex operations, use an orchestrator action that calls multiple atomic actions.

```php
declare(strict_types=1);

namespace Modules\Order\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\Order\Models\Order;
use Modules\Order\Payloads\V1\CheckoutPayload;
use Modules\Order\Events\OrderCreated;

/**
 * Class CheckoutAction
 *
 * Orchestrates the checkout process by coordinating multiple actions.
 *
 * @package Modules\Order\Actions
 */
final readonly class CheckoutAction
{
    /**
     * CheckoutAction constructor.
     *
     * @param DatabaseManager $database
     * @param CreateOrderAction $createOrder
     * @param ProcessPaymentAction $processPayment
     * @param UpdateInventoryAction $updateInventory
     */
    public function __construct(
        private DatabaseManager $database,
        private CreateOrderAction $createOrder,
        private ProcessPaymentAction $processPayment,
        private UpdateInventoryAction $updateInventory,
    ) {}

    /**
     * Handle the checkout process.
     *
     * @param CheckoutPayload $payload
     * @return Order
     * @throws \Throwable
     */
    public function handle(CheckoutPayload $payload): Order
    {
        return $this->database->transaction(function () use ($payload): Order {
            // 1. Create the order (Atomic Action)
            $order = $this->createOrder->handle($payload);

            // 2. Process payment (Atomic Action)
            $this->processPayment->handle($order, $payload->paymentDetails);

            // 3. Update inventory (Atomic Action)
            $this->updateInventory->handle($order);

            // 4. Dispatch event for other modules (e.g., Notification)
            event(new OrderCreated($order));

            return $order;
        });
    }
}
```

---

## 4. Implementation — Cross-Module Communication

### Side-Effects (Events)
Use events to notify other modules of changes.

```php
// modules/Order/Events/OrderCreated.php
final class OrderCreated
{
    public function __construct(public Order $order) {}
}

// modules/Notification/Listeners/SendOrderConfirmation.php
final readonly class SendOrderConfirmation
{
    public function handle(OrderCreated $event): void
    {
        // Notification logic here
    }
}
```

### Data Retrieval (Direct Read)
One module can read another's model to avoid boilerplate.

```php
// modules/Invoice/Actions/GenerateInvoiceAction.php
use Modules\Order\Models\Order; // Direct read from Order module

final readonly class GenerateInvoiceAction
{
    public function handle(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        // ...
    }
}
```

---

## 5. Implementation — Documentation (PHPDoc & Scribe)

Use detailed PHPDocs for automatic documentation generation via Scribe.

```php
/**
 * Store a newly created resource in storage.
 *
 * @group User Management
 * @authenticated
 * @header X-Custom-Header Custom header description.
 *
 * @param V1\StoreUserRequest $request The validated request.
 * @return JsonDataResponse The API response containing the created user.
 *
 * @responseField data.id integer The ID of the user.
 * @responseField data.name string The name of the user.
 */
public function __invoke(StoreUserRequest $request): JsonDataResponse
{
    // ...
}
```

---

## 6. Implementation — Testing (Pest + Factory)

Always use Factories for testing. Ensure high coverage of both success and failure cases.

```php
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can store a user with valid data', function (): void {
    // Setup using Factory
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Action
    $response = $this->actingAs($admin)
        ->postJson('/api/V1/users', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

    // Assertions
    $response->assertStatus(Response::HTTP_CREATED)
        ->assertJsonStructure([
            'success',
            'data' => ['id', 'name', 'email'],
            'message'
        ])
        ->assertJsonPath('data.name', 'Jane Doe');

    $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
});

it('fails to store a user with duplicate email', function (): void {
    $existingUser = User::factory()->create(['email' => 'duplicate@example.com']);
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)
        ->postJson('/api/V1/users', [
            'name' => 'New User',
            'email' => 'duplicate@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('title', 'Validation Error');
});
```

---

## 7. Implementation — Route Definitions (V1.php)

Note the uppercase `V1` and mandatory middleware.

```php
// modules/User/Routes/V1.php
use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\V1\StoreController;
use Modules\User\Controllers\V1\IndexController;

Route::prefix('V1/users')
    ->middleware(['force.json', 'throttle:api', 'auth:sanctum'])
    ->name('users.')
    ->group(function (): void {
        Route::get('/', IndexController::class)->name('index');
        Route::post('/', StoreController::class)->name('store');
    });
```
