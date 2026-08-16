# ADR-0028: EnsureEmailIsVerified Overrides the Native `verified` Middleware

- Status: Accepted
- Date: 2026-08-14

## Context

Laravel ships an `EnsureEmailIsVerified` middleware and registers it under the `verified` alias. The stock middleware is web-oriented: an unverified user is redirected to the `verification.notice` route (a web page).

This kit is API-only. Redirecting a JSON client produces an unusable response, so `bootstrap/app.php` registers `App\Http\Middleware\EnsureEmailIsVerified` under the same `verified` alias. The custom middleware throws `401` (unauthenticated) when no user is present and `403` (problem response `access_denied`) when the user has not verified their email.

This is a deliberate deviation from a native middleware and must be recorded as such (native-first, `docs/architecture.md` section 7.5).

## Decision

The `verified` alias resolves to `App\Http\Middleware\EnsureEmailIsVerified` (an API-adapted version of the native middleware):

- Authenticated but unverified user: `403` problem response (`email_verify_required`).
- No authenticated user: `401` problem response (propagated `AuthenticationException`).
- No redirect to a web verification page; the signed verification URL is delivered through the SPA flow configured in `AppServiceProvider::configureEmailVerificationUrl()`.

The escape hatch is documented: teams with web routes can register `Illuminate\Auth\Middleware\EnsureEmailIsVerified` under a different alias (e.g. `verified.web`) for web flows, or keep both aliases.

## Consequences

- Easier: JSON clients receive a consistent problem-response contract on unverified access.
- Easier: the API-only contract is explicit (the check is unconditional by the `Identity` contract, which extends `MustVerifyEmail`).
- More difficult: a fork that swaps in a model without verification support fails loudly instead of silently skipping the check; teams mixing web and API flows need both aliases.