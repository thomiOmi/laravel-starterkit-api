# ADR-0022: Custom FeatureFlagMiddleware over Native Pennant Middleware

- Status: Accepted
- Date: 2026-08-10

## Context

Pennant ships `EnsureFeaturesAreActive`, which aborts with 400 by default and supports feature classes. The kit already had a `FeatureFlagMiddleware` (403 + `auth.http_forbidden` typeKey) registered as alias `feature.flag`.

## Decision

Keep the custom `FeatureFlagMiddleware` instead of adopting the native Pennant middleware. The `beta-feature` placeholder in `AppServiceProvider` is left untouched (its closure param is non-nullable, so it must not gate public routes).

## Consequences

- Flag-off responses stay consistent with the kit's problem-details contract (403, `auth.http_forbidden`) instead of Pennant's default 400.
- Feature classes (Pennant class-based features) are not forced; closure definitions suffice for kit flags.
- Route gating stays explicit in route definitions: `feature.flag:iam.self-registration`.
