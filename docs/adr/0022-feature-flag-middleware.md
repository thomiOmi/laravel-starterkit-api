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
- 2026-11-24: two details of the text above are inaccurate. First, `auth.http_forbidden` is a translation key for the exception message (`app/Http/Middleware/EnsureFeatureIsActive.php:36`, `lang/en/auth.php:42`), not the problem `typeKey`; a flag-off response is rendered by the `AccessDeniedHttpException` handler as `ProblemResponse` with `typeKey: 'forbidden'` and `title: __('auth.http_forbidden')` (`bootstrap/app.php:92-107`), and `config/errors.php:29` maps `forbidden` to the slug `access-denied`, so the client receives `type` `{ERROR_DOCS_URL}/access-denied` (`config/errors.php:15`, `app/Http/Responses/ProblemResponse.php:96-109`), `title` `Forbidden`, `status` `403`, and `detail` equal to the exception message (or `auth.access_denied` when empty). Second, the "central registry features map" wording in decision 1 is still misleading after the 2026-11-09 correction: build-time flags are declared per module in `modules/{Module}/config/config.php` under `features`, merged by the base `Nwidart\Modules\Support\ModuleServiceProvider` under the lowercase module alias, and read with `config()->has()`/`config()->boolean()` (`app/Http/Middleware/EnsureFeatureIsActive.php:47-55`); `config/modules.php` has no `features` or `active` key. (Original text above is kept as written.)
