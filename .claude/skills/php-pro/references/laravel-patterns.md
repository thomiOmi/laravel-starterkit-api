# Laravel Patterns

## Action Pattern (Preferred)

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\CreateUserData;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Support\Facades\Hash;

final readonly class CreateUserAction
{
    public function __construct(
        private EmailService $emailService,
    ) {}

    public function handle(CreateUserData $data): User
    {
        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);

        $this->emailService->sendWelcomeEmail($user);

        return $user;
    }
}
```

## Service Layer Pattern

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Enums\UserStatus;

final readonly class UserService
{
    public function __construct(
        private EmailService $emailService,
    ) {}

    public function suspendUser(string $userId, string $reason): void
    {
        $user = User::findOrFail($userId);

        $user->update([
            'status' => UserStatus::SUSPENDED,
            'suspension_reason' => $reason,
            'suspended_at' => now(),
        ]);

        $this->emailService->sendSuspensionNotice($user, $reason);
    }
}
```

## API Resources

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status->value,
            'role' => $this->role->value,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),

            // Conditional relationships
            'posts' => PostResource::collection($this->whenLoaded('posts')),
            'profile' => new ProfileResource($this->whenLoaded('profile')),
        ];
    }
}
```

## Controllers (Single-Action)

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Resources\UserResource;
use App\Actions\CreateUserAction;
use Illuminate\Http\JsonResponse;

final readonly class UserCreateController
{
    public function __construct(
        private CreateUserAction $action,
    ) {}

    public function __invoke(CreateUserRequest $request): JsonResponse
    {
        $user = $this->action->handle($request->toDto());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }
}
```

## Quick Reference

| Pattern | Purpose | File Location |
|---------|---------|---------------|
| Action | Single business logic | `Modules/{Module}/Actions/` |
| Service | Domain logic orchestration | `Modules/{Module}/Services/` |
| Form Request | Validation | `Modules/{Module}/Requests/` |
| Resource | API responses | `Modules/{Module}/Resources/` |
| Job | Async tasks | `Modules/{Module}/Jobs/` |
| Event | Domain events | `Modules/{Module}/Events/` |
| Payload | Data transfer | `Modules/{Module}/Payloads/` |
