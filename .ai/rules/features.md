---
paths:
  - 'modules/*/app/Features/**'
  - 'app/Features/**'
  - 'config/modules.php'
  - 'stubs/feature.stub'
---

# Features

## Goal

Module feature flags. Build-time toggle: the `features` array in the central registry (`config/modules.php`). Runtime per-user: Pennant classes in `modules/{Module}/Features/` (used by 2+ modules: `app/Features/`), checked via `EnsureFeatureIsActive`.

## Rules

1. Build-time: boolean values in the registry; merged by the base provider into `config('{alias}.features')`
2. Runtime: `final class {Feature}` (no parent class — `Laravel\Pennant\Feature` is the facade, class features stand alone), `resolve()` contains per-user logic
3. Generate app-level features with `php artisan pennant:feature {Name}` — the command renders `stubs/feature.stub` (kit convention: `declare(strict_types=1)`, final class, `resolve(mixed $scope): bool`). Module-only features are hand-created in `modules/{Module}/Features/` following the same shape.
4. Reference class features by `::class` (e.g. `BetaFeature::class`), never by a string name
5. Naming: `{module}.{feature}` (e.g. `iam.self-registration`)
6. Unregistered features are considered off (default false)
7. Pennant classes are only for runtime decisions (per-user, gradual rollout); static toggles just use the features array in the registry

## Forbidden

- No `env()` for feature toggles
- No two sources of truth (registry vs Pennant for the same thing)

## Example

```php
final class BetaFeature
{
    public function resolve(Identity $user): bool
    {
        return $user->hasRole(RoleEnum::Admin->value); // per-user runtime decision
    }
}
```