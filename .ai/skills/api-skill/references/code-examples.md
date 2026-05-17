# Comprehensive Code Examples

This document provides full implementations of common patterns.

---

## 1. Complete CRUD Example (Store Operation)

### Route (`modules/Blog/Routes/V1.php`)
```php
Route::prefix('V1/posts')->middleware(['force.json', 'throttle:api', 'auth:sanctum'])->group(function() {
    Route::post('/', \Modules\Blog\Controllers\V1\StoreController::class)->name('blog.posts.store');
});
```

### Form Request (`modules/Blog/Requests/V1/StorePostRequest.php`)
```php
final class StorePostRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ];
    }

    public function payload(): StorePostPayload
    {
        return StorePostPayload::from($this->validated());
    }
}
```

### Action (`modules/Blog/Actions/StorePostAction.php`)
```php
final readonly class StorePostAction
{
    public function __construct(private DatabaseManager $database) {}

    public function handle(StorePostPayload $payload): Post
    {
        return $this->database->transaction(fn() => Post::create($payload->toArray()));
    }
}
```

---

## 2. The Orchestrator Pattern (Complex Logic)

Used when one process involves multiple domains.

```php
final readonly class CheckoutAction
{
    public function __construct(
        private DatabaseManager $database,
        private CreateOrderAction $createOrder,
        private ProcessPaymentAction $processPayment,
        private UpdateStockAction $updateStock
    ) {}

    public function handle(CheckoutPayload $payload): Order
    {
        return $this->database->transaction(function() use ($payload) {
            $order = $this->createOrder->handle($payload->orderData);
            $this->processPayment->handle($order, $payload->paymentData);
            $this->updateStock->handle($order->items);

            event(new OrderProcessed($order));

            return $order;
        });
    }
}
```

---

## 3. Testing with Pest and Factories

```php
uses(RefreshDatabase::class);

it('can create a blog post', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/V1/posts', [
            'title' => 'My New Post',
            'content' => 'Lorum Ipsum...',
        ]);

    $response->assertStatus(Response::HTTP_CREATED)
        ->assertJsonPath('data.title', 'My New Post');

    $this->assertDatabaseHas('posts', ['title' => 'My New Post']);
});
```
