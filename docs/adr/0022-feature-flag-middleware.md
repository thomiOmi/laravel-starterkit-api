# ADR-0022: Custom EnsureFeatureIsActive over Native Pennant Middleware

- Status: Accepted
- Date: 2026-08-10

## Context

Pennant ships `EnsureFeaturesAreActive`, which aborts with 400 by default and supports feature classes. The kit already had a `EnsureFeatureIsActive` (403 + `auth.http_forbidden` typeKey) registered as alias `feature-flag`.

## Decision

Keep the custom `EnsureFeatureIsActive` instead of adopting the native Pennant middleware. The middleware resolves a flag in two ways, in order:

1. **Build-time**: a two-segment name (`{alias}.{feature}`, e.g. `iam.self-registration`) that exists in the central registry features map (`config('{alias}.features.{feature}')`, merged by the base `ModuleServiceProvider`) is a build-time decision read from config.
2. **Runtime**: any other name (or one absent from the registry) falls back to Pennant (`Feature::active`), e.g. a Pennant class such as `BetaFeature` in `app/Features/`.

## Consequences

- Flag-off responses stay consistent with the kit's problem-details contract (403, `auth.http_forbidden`) instead of Pennant's default 400.
- Feature classes (Pennant class-based features) are not forced; closure definitions suffice for runtime flags.
- Route gating stays explicit in route definitions: `feature-flag:iam.self-registration`.
- A build-time flag is a code decision in `config/modules.php`; it is not overridable at runtime via `Feature::activate()`/`deactivate()`. Runtime per-user flags that must change at runtime belong in Pennant (`app/Features/` or `modules/*/Features/`).

## Corrections

- 2026-11-09: the build-time `features` registry is not in `config/modules.php`; each module declares it in `modules/{Module}/config/config.php` (for example `modules/IAM/config/config.php`), and the base `Nwidart\Modules\Support\ModuleServiceProvider` merges that file under the module's lowercase name, so the middleware reads `config('{alias}.features.{feature}')`. (Original text above is kept as written.)
- 2026-11-09: there is currently no `modules/*/Features/` directory or module feature class; runtime features live in `app/Features/` (for example `BetaFeature`). (Original text above is kept as written.)
