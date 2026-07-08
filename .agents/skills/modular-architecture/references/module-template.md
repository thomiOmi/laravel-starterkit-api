# Module Template

Concrete file examples from the `IAM` module. Use these as templates when creating new modules or features.

## Service Provider

`modules/{Module}/Providers/{Module}ServiceProvider.php`

```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class {Module}ServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRoutes();
    }

    public function register(): void {}

    protected function configureRoutes(): void
    {
        Route::prefix('api/v1')
            ->middleware('api')
            ->name('v1.')
            ->group(base_path('modules/{Module}/Routes/V1.php'));
    }
}
```

## Route

`modules/{Module}/Routes/V1.php`

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\{Module}\Controllers\V1\{Resource}CreateController;
use Modules\{Module}\Controllers\V1\{Resource}DeleteController;
use Modules\{Module}\Controllers\V1\{Resource}ListController;
use Modules\{Module}\Controllers\V1\{Resource}ShowController;
use Modules\{Module}\Controllers\V1\{Resource}UpdateController;

Route::prefix('{resources}')
    ->name('{resource}.')
    ->middleware(['auth:sanctum', 'verified', 'throttle:api'])
    ->group(function () {
        Route::get('/', {Resource}ListController::class)->name('index');
        Route::post('/', {Resource}CreateController::class)->name('create');
        Route::get('/{id}', {Resource}ShowController::class)->name('show');
        Route::put('/{id}', {Resource}UpdateController::class)->name('update');
        Route::delete('/{id}', {Resource}DeleteController::class)->name('delete');
    });
```

## Controller

`modules/{Module}/Controllers/V1/{Resource}{Action}Controller.php`

```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Modules\{Module}\Actions\{CreateAction};
use Modules\{Module}\Requests\V1\{Resource}Request;
use Modules\{Module}\Resources\{Resource}Resource;
use Symfony\Component\HttpFoundation\Response;

final readonly class {Resource}CreateController
{
    public function __construct(
        private {CreateAction} $create,
    ) {}

    public function __invoke({Resource}Request $request): SuccessResponse
    {
        $model = $this->create->handle($request->payload());

        return new SuccessResponse(
            data: new {Resource}Resource($model),
            title: 'Created',
            detail: __('general.created', ['resource' => '{Resource}']),
            status: Response::HTTP_CREATED,
        );
    }
}
```

## Action

`modules/{Module}/Actions/{Action}.php`

```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\Actions;

use Modules\{Module}\Models\{Resource};
use Modules\{Module}\Payloads\V1\{Resource}Payload;

final readonly class Create{Resource}Action
{
    public function handle({Resource}Payload $payload): {Resource}
    {
        return {Resource}::create($payload->toArray());
    }
}
```

## Payload

`modules/{Module}/Payloads/V1/{Resource}Payload.php`

```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\Payloads\V1;

use Modules\{Module}\Requests\V1\{Resource}Request;

final readonly class {Resource}Payload
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}

    public static function fromRequest({Resource}Request $request): self
    {
        return new self(
            name: trim($request->string('name')->toString()),
            description: $request->filled('description')
                ? trim($request->string('description')->toString())
                : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
        ], fn (mixed $value) => $value !== null);
    }
}
```

## Request

`modules/{Module}/Requests/V1/{Resource}Request.php`

```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Modules\{Module}\Payloads\V1\{Resource}Payload;

final class {Resource}Request extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->isMethod('POST')
            ? '{resource}.create'
            : '{resource}.edit';

        return $this->user()?->can($permission) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function payload(): {Resource}Payload
    {
        return {Resource}Payload::fromRequest($this);
    }
}
```

## Filter

`modules/{Module}/Filters/{Resource}Filter.php`

```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\Filters;

use Illuminate\Database\Eloquent\Builder;

/** @extends BaseFilter<{Resource}> */
class {Resource}Filter extends BaseFilter
{
    protected array $allowedFilters = [
        'status',
    ];

    protected array $allowedSorts = [
        'name',
        'created_at',
    ];

    protected array $allowedFields = [
        'id',
        'name',
        'description',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function search(Builder $builder, string $value): Builder
    {
        return $this->applySearch($builder, $value, ['name', 'description']);
    }
}
```

## Resource

`modules/{Module}/Resources/{Resource}Resource.php`

```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\Resources;

use App\Concerns\FormatDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {Resource}Resource extends JsonResource
{
    use FormatDates;

    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->resource->id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'created_at' => $this->formatDate($this->resource->created_at) ?? '',
            'updated_at' => $this->formatDate($this->resource->updated_at) ?? '',
            'deleted_at' => $this->formatDate($this->resource->deleted_at),
        ];
    }
}
```

## Model

`modules/{Module}/Models/{Resource}.php`

```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\Models;

use App\Concerns\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description'])]
#[Hidden([])]
#[UseFactory({Resource}Factory::class)]
class {Resource} extends Model
{
    use HasDefaultBehavior, HasFactory;
}
```

## Unit Test

`modules/{Module}/Tests/Unit/Create{Resource}ActionTest.php`

```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\Tests\Unit;

use Modules\{Module}\Actions\Create{Resource}Action;
use Modules\{Module}\Models\{Resource};
use Modules\{Module}\Payloads\V1\{Resource}Payload;

describe('Create{Resource}Action', function () {
    it('creates a new {resource}', function () {
        $action = app(Create{Resource}Action::class);

        $model = $action->handle(new {Resource}Payload(
            name: 'Test {Resource}',
        ));

        expect($model)->toBeInstanceOf({Resource}::class)
            ->name->toBe('Test {Resource}');
    });
});
```

## Feature Test

`modules/{Module}/Tests/Feature/{Resource}ManagementTest.php`

```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\Tests\Feature;

use Modules\{Module}\Models\{Resource};

beforeEach(function () {
    $this->admin = loginAsUser();
});

describe('{Resource} Lifecycle', function () {
    it('creates a new {resource}', function () {
        $response = $this->postJson('/api/v1/{resources}', [
            'name' => 'Test {Resource}',
        ]);

        expect($response)->toBeSuccessResponse(status: 201);
    })->group('v1');

    it('lists {resources} with pagination', function () {
        {Resource}::create(['name' => 'Sample']);

        $response = $this->getJson('/api/v1/{resources}');

        expect($response)->toBeSuccessResponse()
            ->toBePaginated();
    })->group('v1');
});
```
