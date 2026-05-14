# Background Jobs & Queue Standards

We prefer non-blocking API responses to ensure a smooth user experience. When an operation can be deferred, we dispatch a job and return a `202 Accepted` status.

## 1. When to use Background Jobs

- **Non-blocking**: For any task that doesn't need to finish before the user gets a response (e.g., sending emails, processing uploads, complex calculations).
- **Blocking Exceptions**:
    - User Registration/Login (token needed immediately).
    - Operations where the client needs the resulting data to proceed.

## 2. Job Implementation

- **Traits**: Use `Queueable` and `SerializesModels`.
- **Model Injection**: When a job receives an Eloquent model, do not use `readonly` on the property because `SerializesModels` rehydrates the model via `__wakeup()`.
- **Transactions**: Wrap job logic in a database transaction.

### Example Job (`modules/Post/Jobs/DeletePostJob.php`)

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
        private Post $post, // not readonly — rehydrated via SerializesModels
    ) {}

    public function handle(DatabaseManager $database): void
    {
        $database->transaction(
            callback: fn (): bool => $this->post->delete(),
        );
    }
}
```

## 3. Controller Response

When dispatching a job from an Action, the controller should return `202 Accepted`.

```php
// In Controller
return $this->successResponse(
    data: null,
    message: 'Request accepted for processing',
    status: Response::HTTP_ACCEPTED
);
```

## 4. Anti-Patterns

- ❌ Do not perform long-running tasks synchronously.
- ❌ Do not use `readonly` for Model properties in Jobs using `SerializesModels`.
- ❌ Do not forget to wrap job logic in a transaction.
- ❌ Do not return `200 OK` or `201 Created` for tasks that haven't finished yet (use `202`).
