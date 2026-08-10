# ADR-0004: Identity Contract for User Type-Hinting

- Status: Accepted
- Date: 2026-08-03

## Context

Feature modules must not import `Modules\IAM\Models\User` directly (module isolation). App-layer code (middleware, requests, responses) needs to type-hint the authenticated user without depending on the concrete model.

## Decision

Introduce `App\Contracts\Identity` as the abstraction for user type-hinting in the `app/` layer. Modules communicate through contracts, never through direct model imports. Roles are eager-loaded narrowly as `roles:id,name,guard_name` to satisfy Spatie guard checks without lazy-loading.

## Consequences

- App-layer code depends only on the contract; swapping the user model (a fork) does not break it.
- Module isolation is enforced by architecture tests (`modules should be isolated`).
- The contract extends `MustVerifyEmail`, so verification behavior is guaranteed at runtime.
