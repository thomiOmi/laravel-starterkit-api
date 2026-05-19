# Code Examples (2026 Edition)

This document provides updated standard code examples for the modular API architecture.

---

## 1. Single Action Controller (PHP 8.4 & Attributes)

```php
declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Modules\User\Actions\IndexUserAction;
use Modules\User\Requests\V1\IndexRequest;
use Modules\User\Resources\UserResource;
use Knuckles\Scribe\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

#[Group("User Management")]
final readonly class IndexController
{
    public function __construct(
        private IndexUserAction $action
    ) {}

    public function __invoke(IndexRequest $request): JsonDataResponse
    {
        $users = $this->action->handle($request->payload());

        return new JsonDataResponse(
            data: UserResource::collection($users),
            status: Response::HTTP_OK
        );
    }
}
```

---

## 2. Payload with Property Hooks (PHP 8.4)

```php
declare(strict_types=1);

namespace Modules\User\Payloads\V1;

final readonly class StoreUserPayload
{
    public string $email {
        set => strtolower(trim($value));
    }

    public string $name {
        set => ucwords(trim($value));
    }

    public function __construct(
        string $email,
        string $name,
        public string $password,
    ) {
        $this->email = $email;
        $this->name = $name;
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
            'password' => $this->password,
        ];
    }
}
```

---

## 3. Action with Defer & Context (Laravel 13)

```php
declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Context;
use Modules\User\Models\User;
use Modules\User\Payloads\V1\StoreUserPayload;
use function Illuminate\Support\defer;

final readonly class StoreUserAction
{
    public function __construct(
        private DatabaseManager $database
    ) {}

    public function handle(StoreUserPayload $payload): User
    {
        return $this->database->transaction(function () use ($payload) {
            $user = User::query()->create($payload->toArray());

            // Using Context for traceable logging
            \Log::info("User created", ['user_id' => $user->id, 'trace_id' => Context::get('trace_id')]);

            // Using defer() for post-response tasks (Laravel 13)
            defer(fn () => $user->sendWelcomeNotification());

            return $user;
        });
    }
}
```

---

## 4. Modern BaseFilter Implementation

```php
declare(strict_types=1);

namespace Modules\User\Filters;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Modules\User\Models\User;

/**
 * @extends BaseFilter<User>
 */
final class UserFilter extends BaseFilter
{
    public function apply(Builder $query): Builder
    {
        return $query
            ->when($this->request->string('search'), function (Builder $query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($this->request->string('status'), fn (Builder $q, $s) => $q->where('status', $s));
    }
}
```

---

## 5. Pest Architecture Test

```php
declare(strict_types=1);

test('controllers and actions must be final')
    ->expect(['Modules', 'App\Http\Controllers'])
    ->toBeFinal();

test('controllers must not access models directly')
    ->expect('Modules\*\Controllers')
    ->not->toUse('Modules\*\Models');

test('actions must be readonly')
    ->expect('Modules\*\Actions')
    ->toBeReadonly();
```
