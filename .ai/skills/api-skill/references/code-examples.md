# Code Examples

This document provides complete, copy-pasteable examples of the modular API pattern.

---

## 1. Single Action Controller with Filter

```php
declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\User\Models\User;
use Modules\User\Resources\UserResource;
use Modules\User\Filters\UserFilter;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group User Management
 */
final readonly class IndexController
{
    public function __invoke(UserRequest $request, UserFilter $filter): JsonDataResponse
    {
        $users = User::query()
            ->filter($filter)
            ->simplePaginate($request->integer('per_page', 15));

        return new JsonDataResponse(
            data: UserResource::collection($users),
            status: Response::HTTP_OK
        );
    }
}
```

---

## 2. Action with Spatie Permission Check

```php
declare(strict_types=1);

namespace Modules\Blog\Actions;

use Illuminate\Database\DatabaseManager;
use Modules\Blog\Models\Post;
use Modules\Blog\Payloads\V1\UpdatePostPayload;

final readonly class UpdatePostAction
{
    public function __construct(private DatabaseManager $database) {}

    public function handle(Post $post, UpdatePostPayload $payload): Post
    {
        return $this->database->transaction(function () use ($post, $payload) {
            $post->update($payload->toArray());
            return $post;
        });
    }
}
```

### Corresponding Policy
```php
final class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        // Spatie Permission check
        return $user->hasPermissionTo('posts.update') && $user->id === $post->user_id;
    }
}
```

---

## 3. Pest Feature Test with Factory

```php
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can fetch users list with filter', function (): void {
    User::factory()->count(5)->create(['name' => 'Active User']);
    User::factory()->count(2)->create(['name' => 'Inactive User']);

    $admin = User::factory()->create();
    $admin->assignRole('admin'); // Spatie helper

    $this->actingAs($admin)
        ->getJson('/api/V1/users?search=Active')
        ->assertStatus(Response::HTTP_OK)
        ->assertJsonCount(5, 'data');
});
```

---

## 4. Custom Middleware (API Foundation)

These middlewares ensure consistent API behavior and deprecation signaling.

### Force JSON Response
Forces the `Accept: application/json` header on all incoming requests.

```php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
```

### Sunset Middleware (RFC 8594)
Used to signal the retirement date of a deprecated API version.

```php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SunsetMiddleware
{
    public function handle(Request $request, Closure $next, string $date): Response
    {
        $response = $next($request);

        $response->headers->set('Sunset', $date);
        $response->headers->set(
            'Deprecation',
            'This API version is deprecated and will be removed on ' . $date
        );

        return $response;
    }
}
```
