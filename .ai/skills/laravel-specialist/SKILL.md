---
name: laravel-specialist
description: Expert in Laravel 13, PHP 8.4, and Modular Monolith development. Handles Model creation with Property Hooks, Modular Controllers, and Action-Payload implementation.
license: MIT
metadata:
  version: "2.3.0"
---

# Laravel Specialist (L13 & PHP 8.4)

Senior guidance for building robust, modular Laravel 13 applications using modern PHP features.

## Gotchas
- **Cross-Module Imports:** Never `use Modules\Auth\Models\User` in `Modules\Sales`. Use `app/Contracts/Identity` instead.
- **Lazy Loading:** Model lazy loading is disabled in dev. Always use `with()` or `load()`.
- **Property Hooks:** Hooks are native PHP 8.4. Do not use Laravel `Attribute` classes unless specifically required for legacy compatibility.
- **Final Classes:** Forgetting `final` on a class violates the project's safety guidelines.

## 1. Modular Model Template
Use `HasDefaultBehavior` for consistent ULID, SoftDeletes, and Date formatting.

```php
<?php

declare(strict_types=1);

namespace Modules\Catalog\Models;

use App\Concerns\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Product extends Model
{
    use HasDefaultBehavior;

    /**
     * PHP 8.4 Property Hooks for derived data.
     */
    public string $priceFormatted {
        get => number_format($this->price_cents / 100, 2) . ' USD';
    }

    public string $slug {
        set(string $value) => str($value)->slug()->toString();
        get => $this->slug;
    }

    protected $fillable = ['name', 'price_cents', 'slug', 'category_id'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
```

## 2. Action & Payload Pattern
Actions encapsulate business logic, while Payloads provide type-safe input.

### Template: CreateProductPayload
```php
<?php

declare(strict_types=1);

namespace Modules\Catalog\Payloads;

final readonly class CreateProductPayload
{
    public function __construct(
        public string $name,
        public int $priceCents,
        public string $categoryId,
    ) {}

    public function toArray(): array
    {
        return [
            'name'        => $this->name,
            'price_cents' => $this->priceCents,
            'category_id' => $this->categoryId,
        ];
    }
}
```

### Template: CreateProductAction
```php
<?php

declare(strict_types=1);

namespace Modules\Catalog\Actions;

use Modules\Catalog\Models\Product;
use Modules\Catalog\Payloads\CreateProductPayload;
use Illuminate\Support\Facades\DB;

final readonly class CreateProductAction
{
    public function handle(CreateProductPayload $payload): Product
    {
        return DB::transaction(function () use ($payload): Product {
            // Logic before creation (e.g., validation, events)
            $product = Product::create($payload->toArray());

            // Logic after creation (e.g., image processing, notifications)

            return $product;
        });
    }
}
```

## 3. Modular Controller (V1)
Controllers should be invokable and thin.

```php
<?php

declare(strict_types=1);

namespace Modules\Catalog\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Modules\Catalog\Actions\CreateProductAction;
use Modules\Catalog\Requests\StoreProductRequest;
use Modules\Catalog\Resources\ProductResource;

final readonly class StoreProductController extends Controller
{
    public function __invoke(StoreProductRequest $request, CreateProductAction $action): SuccessResponse
    {
        // Request -> Payload -> Action
        $product = $action->handle($request->toPayload());

        return new SuccessResponse(new ProductResource($product));
    }
}
```

## Constraints
- **MUST** run `./vendor/bin/pint --format agent` before finishing code tasks.
- **MUST** use `SuccessResponse` or `ProblemResponse`.
- **MUST** read `references/architecture.md` for module communication rules.
