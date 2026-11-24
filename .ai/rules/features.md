---
paths:
  - 'modules/*/app/Features/**'
  - 'app/Features/**'
  - 'config/modules.php'
  - 'stubs/feature.stub'
---

# Features

## Goal

Module feature flags. Build-time toggle: the `features` array in the module's `config/config.php` (merged into `config('{alias}.features')`). Runtime per-user: Pennant classes in `modules/{Module}/app/Features/` (used by 2+ modules: `app/Features/`), checked via `EnsureFeatureIsActive`.

## Rules

1. Build-time: boolean values in the module's `config/config.php`; merged by the base provider into `config('{alias}.features')`
2. Runtime: `final class {Feature}` (no parent class — `Laravel\Pennant\Feature` is the facade, class features stand alone), `resolve()` contains per-user logic
3. Generate app-level features with `php artisan pennant:feature {Name}` - the command renders `stubs/feature.stub` (kit convention: `declare(strict_types=1)`, final class, `resolve(mixed $scope): bool` returning `false`). Module-only features are hand-created in `modules/{Module}/app/Features/` following the same shape. The generic `mixed $scope` in the stub is a template, not a requirement: a concrete feature may narrow the parameter to the type it actually receives, as `BetaFeature` narrows it to `Identity $user`. For a null scope (guest request, Artisan command, queued job), Pennant does not call `resolve()` and returns `false` while dispatching `UnexpectedNullScopeEncountered` only when `resolve()` declares a scope type that does not accept null, as `BetaFeature` does with `Identity $user`; with the stub's `mixed $scope`, the resolver is still called with null. See the Pennant 13.x "Nullable Scope" section (https://laravel.com/docs/13.x/pennant) and `tests/Feature/Features/BetaFeatureTest.php`.
4. Reference class features by `::class` (e.g. `BetaFeature::class`), never by a string name; this rule is for Pennant class features. Build-time flags use the string form `{module}.{feature}` from rule 5, because the middleware argument is a string.
5. Naming: `{module}.{feature}` (e.g. `iam.self-registration`)
6. Unregistered features are considered off (default false)
7. Pennant classes are only for runtime decisions (per-user, gradual rollout); static toggles just use the features array in the registry
8. Inert by default: an unregistered or undefined flag is off; the default value shipped by the kit does not change baseline behavior (for example `iam.self-registration` is `true` so registration remains available as the baseline). `BetaFeature` is a sample class and is not used by any route.

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