# Conventions Reference

This document covers folder structure, naming conventions, and complete worked examples for the API skill.

---

## Folder Structure

This project follows a strict **Domain-Driven Modular Architecture**. All domain logic must reside within modules.

```text
modules/
  {Module}/
    Actions/         # Business logic classes
    Controllers/     # V1, V2 single-action controllers
    Payloads/        # Data Transfer Objects
    Models/          # Eloquent models
    Requests/        # Form requests
    Resources/       # Eloquent resources
    Routes/          # v1.php, v2.php
    Filters/         # Query filters (extending BaseFilter)
    Database/        # Migrations, Factories, Seeders
    Tests/           # Feature and unit tests
    Providers/       # Module-specific service providers
```

---

## Naming Conventions

| Layer | Convention | Example |
|---|---|---|
| **Controller** | `{Action}Controller` | `StoreController`, `DestroyController` |
| **Action** | `{Action}{Resource}Action` | `StoreUserAction`, `DeleteUserAction` |
| **Payload** | `{Action}{Resource}Payload` | `StoreUserPayload`, `UpdateUserPayload` |
| **Form Request** | `{Action}{Resource}Request` | `StoreUserRequest`, `UserRequest` |
| **API Resource** | `{Resource}Resource` | `UserResource`, `PostResource` |
| **Job** | `{Action}{Resource}Job` | `DeletePostJob` |
| **Filter** | `{Resource}Filter` | `UserFilter`, `PostFilter` |
| **Route Name** | `{module_name}.{action}` | `users.store`, `users.index` |
| **Test File** | `{Action}Test.php` | `StoreTest.php`, `IndexTest.php` |
| **Database Table** | Snake case, plural | `users`, `posts`, `user_roles` |
| **Model** | Pascal case, singular | `User`, `Post`, `UserRole` |

---

## Complete Worked Example — Storing a Resource

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

## Complete Worked Example — Deleting via Background Job

### Job (`modules/Post/Jobs/DeletePostJob.php`)
```php
final class DeletePostJob implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        private Post $post, // not readonly
    ) {}

    public function handle(DatabaseManager $database): void
    {
        $database->transaction(fn () => $this->post->delete());
    }
}
```

---

## Complete Worked Example — Synchronous Registration

### Action (`Modules\Auth\Actions\RegisterAction.php`)
```php
public function execute(RegisterPayload $payload): array
{
    return $this->database->transaction(function () use ($payload): array {
        $user = User::query()->create($payload->toArray());
        $token = $user->createToken('api-token')->plainTextToken;

        return compact('user', 'token');
    });
}
```

---

## Implementation Details

### ProblemResponse Class (RFC 9457)
```php
final readonly class ProblemResponse implements Responsable
{
    public function __construct(
        private string $type,
        private string $title,
        private int    $status,
        private string $detail,
        private array  $errors = [],
    ) {}

    public function toResponse($request): JsonResponse
    {
        return new JsonResponse(
            data: array_filter([
                'type'   => $this->type,
                'title'  => $this->title,
                'status' => $this->status,
                'detail' => $this->detail,
                'errors' => $this->errors ?: null,
            ]),
            status:  $this->status,
            headers: ['Content-Type' => 'application/problem+json'],
        );
    }
}
```

---

## External References

- **Laravel 13**: [https://laravel.com/docs/13.x](https://laravel.com/docs/13.x)
- **RFC 9457 (Problem Details)**: [https://www.rfc-editor.org/rfc/rfc9457](https://www.rfc-editor.org/rfc/rfc9457)
- **RFC 8594 (Sunset Header)**: [https://www.rfc-editor.org/rfc/rfc8594](https://www.rfc-editor.org/rfc/rfc8594)
- **Spatie Permission**: [https://spatie.be/docs/laravel-permission](https://spatie.be/docs/laravel-permission)
