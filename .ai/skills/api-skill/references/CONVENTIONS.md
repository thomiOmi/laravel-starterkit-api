# Conventions Reference

This document covers folder structure, naming conventions, and complete worked examples for the API skill.

---

## Folder Structure

This project follows a strict **Domain-Driven Modular Architecture**. All domain logic must reside within versioned modules.

```text
modules/
  {Module}/
    Actions/            # Business logic classes (usually shared across versions)
    Controllers/
      V1/               # Versioned single-action controllers
        IndexController.php
        StoreController.php
    Payloads/
      V1/               # Versioned Data Transfer Objects
        StorePayload.php
    Models/             # Eloquent models (shared)
    Requests/
      V1/               # Versioned form requests
        StoreRequest.php
    Resources/          # Eloquent resources (can be versioned if needed)
    Routes/
      v1.php            # Version-specific route definitions
    Filters/            # Query filters (extending BaseFilter)
    Database/           # Migrations, Factories, Seeders
    Tests/
      Feature/
        V1/             # Versioned feature tests
          StoreTest.php
    Providers/          # Module-specific service providers
```

---

## Naming Conventions

| Layer | Convention | Example |
|---|---|---|
| **Controller** | `V{Version}\{Action}Controller` | `V1\StoreController` |
| **Action** | `{Action}{Resource}Action` | `StoreUserAction` |
| **Payload** | `V{Version}\{Action}{Resource}Payload` | `V1\StoreUserPayload` |
| **Form Request** | `V{Version}\{Action}{Resource}Request` | `V1\StoreUserRequest` |
| **API Resource** | `{Resource}Resource` | `UserResource` |
| **Job** | `{Action}{Resource}Job` | `DeletePostJob` |
| **Filter** | `{Resource}Filter` | `UserFilter` |
| **Route Name** | `{module_name}.{action}` | `users.store` |
| **Test File** | `V{Version}\{Action}Test.php` | `V1\StoreTest.php` |

---

## Complete Worked Example — Storing a Resource

### Payload (`modules/Post/Payloads/V1/StorePostPayload.php`)
```php
<?php

declare(strict_types=1);

namespace Modules\Post\Payloads\V1;

final readonly class StorePostPayload
{
    public function __construct(
        public string $title,
        public string $content,
        public string $userId,
    ) {}

    public function toArray(): array
    {
        return [
            'title'   => $this->title,
            'content' => $this->content,
            'user_id' => $this->userId,
        ];
    }
}
```

### Form Request (`modules/Post/Requests/V1/StorePostRequest.php`)
```php
<?php

declare(strict_types=1);

namespace Modules\Post\Requests\V1;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Post\Payloads\V1\StorePostPayload;

final class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('post.create');
    }

    public function rules(): array
    {
        return [
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ];
    }

    public function payload(): StorePostPayload
    {
        return new StorePostPayload(
            title:   $this->string('title')->toString(),
            content: $this->string('content')->toString(),
            userId:  $this->user()->id,
        );
    }
}
```

### Action (`modules/Post/Actions/StorePostAction.php`)
```php
<?php

declare(strict_types=1);

namespace Modules\Post\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\Post\Models\Post;
use Modules\Post\Payloads\V1\StorePostPayload;

final class StorePostAction
{
    public function __construct(
        private readonly DatabaseManager $database,
    ) {}

    public function execute(StorePostPayload $payload): Post
    {
        return $this->database->transaction(
            callback: fn (): Post => Post::query()->create(
                attributes: $payload->toArray(),
            ),
        );
    }
}
```

### Controller (`modules/Post/Controllers/V1/StoreController.php`)
```php
<?php

declare(strict_types=1);

namespace Modules\Post\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Post\Actions\StorePostAction;
use Modules\Post\Requests\V1\StorePostRequest;
use Modules\Post\Resources\PostResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Post
 */
final class StoreController extends Controller
{
    public function __construct(
        private readonly StorePostAction $action,
    ) {}

    public function __invoke(StorePostRequest $request): JsonResponse
    {
        $post = $this->action->execute(
            payload: $request->payload(),
        );

        return $this->successResponse(
            data: new PostResource($post),
            message: 'Post created successfully',
            status: Response::HTTP_CREATED,
        );
    }
}
```

---

## Complete Worked Example — Listing with Filters

### Filter (`modules/Post/Filters/PostFilter.php`)
```php
namespace Modules\Post\Filters;

use App\Filters\BaseFilter;

/**
 * @extends BaseFilter<\Modules\Post\Models\Post>
 */
final class PostFilter extends BaseFilter
{
    public function search(string $value): void
    {
        $this->builder->where('title', 'like', "%{$value}%");
    }
}
```

### Controller (`modules/Post/Controllers/V1/IndexController.php`)
```php
/**
 * @tags Post
 */
final class IndexController extends Controller
{
    #[QueryParameter(name: 'search', description: 'Search by title', type: 'string')]
    public function __invoke(Request $request, PostFilter $filter): JsonResponse
    {
        $posts = Post::query()
            ->applyFilter($filter)
            ->simplePaginate($request->integer('per_page', 15));

        return $this->paginateResponse($posts, PostResource::class);
    }
}
```

---

## External References

- **Laravel 13**: [https://laravel.com/docs/13.x](https://laravel.com/docs/13.x)
- **RFC 9457 (Problem Details)**: [https://www.rfc-editor.org/rfc/rfc9457](https://www.rfc-editor.org/rfc/rfc9457)
- **RFC 8594 (Sunset Header)**: [https://www.rfc-editor.org/rfc/rfc8594](https://www.rfc-editor.org/rfc/rfc8594)
- **Spatie Permission**: [https://spatie.be/docs/laravel-permission](https://spatie.be/docs/laravel-permission)
