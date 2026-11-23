# ADR-0046: First-Party Wrapper Classification (G2)

- Status: Accepted
- Date: 2026-11-23

## Context

Gate G2 of P3 requires every first-party wrapper to be classified as keep, simplify, or remove, with a reason that does not go stale. For this decision a wrapper is a first-party class in `app/` or `modules/*/app/` that wraps or replaces a Laravel or vendor capability. The audit was made on `main` at `cf04540`, before the code change delivered in PR #427.

Layers governed by ADR-0026 (Actions, Services, Payloads, Models, Policies, Controllers, Resources) are out of scope. Media internals already decided by ADR-0030, ADR-0031, and ADR-0036 through ADR-0041 are likewise out of scope, except where a public seam or a thin helper needs an explicit classification.

## Decision

### Rubric

- Keep when the class adds behavior the native API does not have, acts as a documented extension seam (an interface plus a default implementation), or is enforced by the architecture test or an `.ai/rules` convention.
- Simplify or remove when the class only forwards to a native API without adding behavior, or when it has no consumer at all.
- Call-site counts are a signal, not a criterion: idiomatic Laravel classes such as validation rules, Artisan commands, notifications, and providers naturally have a single registration point, and that registration is their contract rather than redundancy.

### Per-group decisions

| Group | Classes | Decision | Reason |
|---|---|---|---|
| Responses | `SuccessResponse`, `ProblemResponse` | keep | They define the success envelope and RFC 9457 problem details that no native Laravel response provides, and the architecture test requires response classes to implement `Responsable` (see also ADR-0011). |
| Middleware | `AddSecurityHeaders`, `AddTraceId`, `EnsureEmailIsVerified`, `EnsureFeatureIsActive`, `EnsureUserIsActive`, `HandleIdempotentRequests`, `SetLocale` | keep | Each adds API-specific behavior - security headers, trace context, problem-response auth failures, build-time plus Pennant flags, account-status gating, opt-in idempotency, and locale negotiation - backed by ADR-0018, ADR-0022, and ADR-0028. |
| Dormant middleware | `AddDeprecationHeaders` (`sunset` alias) | keep | No route declares the alias yet, but ADR-0027 reserves this middleware for announcing V1 deprecation, so removal would discard a planned capability. |
| Requests | `PaginationRequest`, `BulkActionRequest` | keep | `PaginationRequest` is the shared validation contract extended by the module list requests, while `BulkActionRequest` stays in `app/` with its own validation and bulk authorization despite being IAM-only (ADR-0045). |
| Builders | `BaseQueryBuilder` and its subclasses, including `PersonalAccessTokenBuilder` | keep | The whitelist contract is the only query-filtering mechanism and is architecture-tested (ADR-0026 D1); `PersonalAccessTokenBuilder` demonstrates the native-first registration via `#[UseEloquentBuilder]` while Sanctum's default token model stays usable (`docs/architecture.md` section 7.5). |
| Concerns | `FormatDate`, `HasDefaultBehavior`, `PasswordValidationRules`, `ProfileValidationRules` | keep | They compose native date, Eloquent, and validation facilities into one shared policy for models and requests, and ADR-0045 explicitly keeps the app-side validation helpers in place. |
| Contracts | `Identity` | keep | ADR-0045 keeps `App\Contracts\Identity` in `app/` so a module can type-hint the authenticated user without importing the IAM model, carrying forward ADR-0004. |
| Support and Console | `ModuleDependencyCheck`, `ProductionSecurityCheck`, `CheckReportRenderer`, `ModuleDependencyCheckCommand`, `ProductionSecurityCheckCommand` | keep | The support classes implement the documented dependency and production-security policies, the commands are auto-discovered by Laravel from `app/Console/Commands` and serve as documented gates, and `CheckReportRenderer` keeps their table output and exit status consistent. |
| Providers | `AppServiceProvider`, module providers, IAM and Media `EventServiceProvider` | keep | The app provider centralizes framework wiring and module providers are the nwidart extension contract (ADR-0029); the empty `configureEmailVerification()` overrides do not drop the framework listener because Laravel auto-registers the base `EventServiceProvider`, whose own hook still registers `Registered` -> `SendEmailVerificationNotification`. |
| Framework overrides | `PersonalAccessToken`, `ResetPassword`, `VerifyEmail` | keep | `PersonalAccessToken` extends the Sanctum token model with kit fillable, builder, and cast attributes (ADR-0001); the two notification subclasses add `ShouldQueue`, `Queueable`, and retry attributes, while `AppServiceProvider` customizes the verification URL and both mail messages on the native base classes they extend; ADR-0045 records only their placement in `app/`. |
| Media | `HasMedia`, `InteractsWithMedia`, public interfaces with default implementations, Rules, plus `FileHash`, `ImageManipulations`, `NullScanner` | keep | Public seams and rules are governed by ADR-0030, ADR-0031, and ADR-0036 through ADR-0041; the other three are accepted as internal details - a nullable streaming hash helper, a thin translator from kit manipulation arrays to `Illuminate\Image`, and the no-op default implementation of the `MediaScanner` seam. |

### Exceptions

- Removed: the app `RouteServiceProvider`, because `withRouting(web: ...)` in `bootstrap/app.php` already loads `routes/web.php`; delivered in PR #427.
- Annotated: the Media `EventServiceProvider` override gained `#[Override]` and the same explanatory docblock as IAM, without behavior change.
- Deferred: `AddDeprecationHeaders` to P4; `BetaFeature` stays as a sample Pennant class until P6 decides its fate. These are roadmap items under issue #225; no issue was created for them.

## Consequences

- P3 requires no further refactor; the codebase is judged lean as it stands.
- The simplify candidates from the audit are explicitly rejected above so the same audit is not repeated.
- References to the app `RouteServiceProvider` in ADR-0008 and ADR-0024 remain historical records; those ADRs are not edited.
- Re-review is triggered when a native counterpart gains equivalent behavior, or when a wrapper loses its last consumer.
