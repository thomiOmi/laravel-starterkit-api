# Controller Standards

This project mandates the use of **Single-Action Invokable Controllers**. Each controller class must handle exactly one responsibility.

## 1. Core Principles

- **Final Classes**: All controllers must be declared as `final`.
- **Invokable**: Use the `__invoke()` method instead of multiple action methods.
- **Constructor Injection**: All dependencies (Actions, Repositories, Services) must be injected via the constructor.
- **Strict Typing**: All methods and properties must be fully typed.
- **No Facades**: Avoid using Facades, `app()`, or `resolve()` inside controllers.

## 2. Directory Structure

Controllers are organized by resource and version within their respective modules:

```text
modules/
  {Module}/
    Controllers/
      V1/
        IndexController.php
        StoreController.php
        ShowController.php
        UpdateController.php
        DestroyController.php
```

## 3. Implementation Example

```php
<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\Actions\StoreUserAction;
use Modules\User\Requests\UserRequest;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags User
 */
final class StoreController extends Controller
{
    /**
     * Create a new StoreController instance.
     */
    public function __construct(
        private readonly StoreUserAction $action
    ) {}

    /**
     * Store a newly created user in storage.
     */
    public function __invoke(UserRequest $request): JsonResponse
    {
        $user = $this->action->execute(
            payload: $request->payload()
        );

        return $this->successResponse(
            data: new UserResource($user),
            message: 'User created successfully',
            status: Response::HTTP_CREATED
        );
    }
}
```

## 4. Why Single-Action?

1. **Single Responsibility Principle (SRP)**: Each class has only one reason to change.
2. **Simplified Context for AI**: AI agents (Claude, Cursor) work much more accurately when focusing on a small, specific file rather than a large resourceful controller.
3. **Better Testability**: Each endpoint is isolated, making it easier to write focused unit and feature tests.
4. **Cleaner Routing**: Route files explicitly list which class handles which endpoint, improving code discoverability.

## 5. Anti-Patterns

- ❌ Do not create multi-method controllers (e.g., `UserController` with `index` and `store`).
- ❌ Do not use Facades inside the `__invoke` method.
- ❌ Do not return raw models or arrays; always use `UserResource` (or similar) and the `successResponse` helper.
- ❌ Do not use bare integers for HTTP status codes (e.g., `201`); use `Symfony\Component\HttpFoundation\Response::HTTP_CREATED`.
