# ADR-0008: Single Route Discovery via RouteServiceProvider

- Status: Accepted
- Date: 2026-08-08

## Context

IAM registered its own routes via `configureRoutes()` in its service provider, plus there was a fallback legacy `routes/api.php`. Two discovery mechanisms produced inconsistent route names (`v1.*` vs `v1.iam.*`).

## Decision

All module routes are discovered by the app `RouteServiceProvider::mapModuleApiRoutes()`, which loads `Modules/{Module}/Routes/V1.php` per version. `configureRoutes()` was removed from the module stub and `IAMServiceProvider`, the IAM skip and the legacy `routes/api.php` fallback were deleted. Route names are uniformly `v1.{module}.{name}`.

## Consequences

- Single source of truth for route registration; no per-module provider routing.
- Route names are deterministic: `v1.iam.register`, `v1.iam.users.index`, etc.
- Verified: 34/34 routes identical in method+uri after the change (names changed from `v1.*` to `v1.iam.*`; 3 name references were updated).
- New modules get route discovery for free from the generated stub.
