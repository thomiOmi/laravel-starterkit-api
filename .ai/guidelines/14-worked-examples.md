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

use Illuminate\Foundation\Http\FormRequest;
use Modules\Post\Payloads\StorePostPayload;

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

## 2. Deleting a Resource via Background Job

### Job (`modules/Post/Jobs/DeletePostJob.php`)
```php
<?php

declare(strict_types=1);

namespace Modules\Post\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Modules\Post\Models\Post;

final class DeletePostJob implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        private Post $post, // rehydrated via SerializesModels
    ) {}

    public function handle(DatabaseManager $database): void
    {
        $database->transaction(
            callback: fn (): bool => $this->post->delete(),
        );
    }
}
```

### Controller (`modules/Post/Controllers/V1/DestroyController.php`)
```php
public function __invoke(Post $post): JsonResponse
{
    dispatch(new DeletePostJob($post));

    return $this->successResponse(
        data: null,
        message: 'Post deletion accepted',
        status: Response::HTTP_ACCEPTED,
    );
}
```

---

## 3. Synchronous Registration

### Action (`Modules\Auth\Actions\RegisterAction.php`)
```php
public function execute(RegisterPayload $payload): array
{
    return $this->database->transaction(function () use ($payload): array {
        /** @var User $user */
        $user = User::query()->create($payload->toArray());

        $token = $user->createToken('api-token')->plainTextToken;

        return compact('user', 'token');
    });
}
```
