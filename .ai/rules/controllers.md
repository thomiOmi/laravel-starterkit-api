---
paths:
  - 'modules/*/app/Http/Controllers/**'
  - 'modules/*/app/Http/Controllers/**'
---

# Controllers

## Goal

Thin HTTP layer: parse the request, call the action, return the response. Single-action invokable classes in `modules/{Module}/app/Http/Controllers/V1/`.

## Rules

1. `final readonly`, extends base `Controller`, one method `__invoke(Request|FormRequest $request): SuccessResponse`; parameters may type-hint FormRequest subclasses (example: `RegisterController`); errors are not returned but thrown as exceptions mapped by the handler to `ProblemResponse` (see error-handling rule 3)
2. Delegate logic to an Action via `->handle()`
3. Pure queries (paginate + BaseQueryBuilder filter/search/sort whitelist, without domain conditions) are allowed directly in controllers
4. Return type-hint `SuccessResponse` (all existing controllers are consistent, 0 usages of `JsonResponse`); `ProblemResponse` is only written by the handler
5. Follow the structure of existing sibling controllers (e.g. modules/IAM/Controllers)

## Forbidden

- No queries with domain conditions in controllers (must go through an Action)
- No business logic
- No non-contract responses (no `success` boolean)

## Example

```php
final readonly class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): SuccessResponse
    {
        $user = (new CreateUserAction)->handle(UserRegistrationPayload::fromRequest($request));

        return new SuccessResponse(
            data: UserResource::make($user),
            status: Response::HTTP_CREATED,
        );
    }
}
```
