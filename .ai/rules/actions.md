---
paths:
  - 'modules/*/app/Actions/**'
---

# Actions

## Goal

Single-responsibility business logic: one `final readonly` class performs exactly one business operation. Called by controllers, called by other actions, or used by services. Inspiration: Fortify Actions.

## Rules

1. `final readonly`, one public `handle()` method, explicitly typed parameters
2. Never receive `Request`; controllers extract data and pass it along
3. No HTTP logic (status codes, redirects, json)
4. Validation happens in the Request layer, not in actions
5. Every action has a unit test in `modules/*/tests/Unit` (Pest, `expect()->toThrow` for exceptions, specific expectations over `toBeTrue()`/`toBeFalse()` wrappers)
6. Business errors via `throw_if`/`throw_unless` + domain exceptions (`InvalidArgumentException` maps to 422, `ModelNotFoundException` to 404 for ownership checks)
7. Interdependent multi-step writes (2+ writes) must be wrapped in `DB::transaction` or equivalent (`saveOrFail`/`deleteOrFail` for instances, `syncOrFail`/`attachOrFail` for pivots); single-model writes use plain `create`/`update`/`save`/`delete`
8. No base class/interface for Actions: the structure is a convention enforced by ArchitectureTest, not inheritance; interfaces only for real cross-module polymorphism (see contracts rule)

## Forbidden

- No public methods other than `handle()`
- No HTTP dependencies (Request, Response)
- No Eloquent queries with inline domain conditions in controllers (queries live in actions or builders; pure BaseQueryBuilder whitelist queries may stay in controllers, see controllers rule 3)
- No HTTP helpers (`abort`, `abort_if`, `abort_unless`) in actions
- No `createOrFail` (does not exist in the framework) and no `updateOrFail`/`deleteOrFail` for lookups (they return false silently when the model does not exist)

## Example

```php
final readonly class CreateUserAction
{
    public function handle(UserPayload $payload): User
    {
        return User::create($payload->toArray());
    }
}
```

## Cross-module features via contracts, consumers inject nullable
A module that depends on another module's capability (e.g. IAM avatar via Media) must not import the other module's classes. Define a contract in app/Contracts (example: AvatarResolver), the owning module implements and binds it in its provider, and the consumer type-hints it as nullable (?AvatarResolver $resolver = null) so the feature degrades gracefully (throws a localized InvalidArgumentException) when the owner module is absent. Laravel's container falls back to the parameter default when an interface has no binding, so no container introspection is needed.
