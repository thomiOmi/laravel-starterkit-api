# Worked Examples

This document provides complete, copy-pasteable examples for the core patterns used in this project.

## 1. Storing a Resource (Post)

### Payload (`modules/Post/Payloads/StorePostPayload.php`)
```php
<?php

declare(strict_types=1);

namespace Modules\Post\Payloads;

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

### Form Request (`modules/Post/Requests/StorePostRequest.php`)
```php
<?php

declare(strict_types=1);

namespace Modules\Post\Requests;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Post\Payloads\StorePostPayload;

#[BodyParameter(name: 'title', description: 'Post title', required: true, example: 'My New Post')]
#[BodyParameter(name: 'content', description: 'Post body content', required: true, example: 'This is the body...')]
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
use Modules\Post\Payloads\StorePostPayload;

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
use Modules\Post\Requests\StorePostRequest;
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

    /**
     * Create a new post.
     */
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

## 2. Listing with Filters (Post)

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
