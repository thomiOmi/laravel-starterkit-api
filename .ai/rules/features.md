---
paths:
  - 'modules/*/Features/**'
  - 'app/Features/**'
  - 'config/modules.php'
---

# Features

## Goal

Module feature flags. Build-time toggle: the `features` array in the central registry (`config/modules.php`). Runtime per-user: Pennant classes in `modules/{Module}/Features/` (used by 2+ modules: `app/Features/`), checked via `FeatureFlagMiddleware`.

## Rules

1. Build-time: boolean values in the registry; merged by the base provider into `config('{alias}.features')`
2. Runtime: `final class {Feature} extends Feature`, `resolve()` contains per-user logic
3. Naming: `{module}.{feature}` (e.g. `iam.self-registration`)
4. Unregistered features are considered off (default false)
5. Pennant classes are only for runtime decisions (per-user, gradual rollout); static toggles just use the features array in the registry

## Forbidden

- No `env()` for feature toggles
- No two sources of truth (registry vs Pennant for the same thing)

## Example

```php
final class MediaUpload extends Feature
{
    public function resolve(User $user): bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin); // per-user runtime decision
    }
}
```
