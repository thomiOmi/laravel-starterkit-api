# ADR-0045: Module Boundaries

- Status: Accepted
- Date: 2026-11-07

## Context

ADR-0004 and ADR-0005 state that modules never import each other and communicate only through contracts in `app/`. The code does not match that claim:

- `modules/IAM/app/Models/User.php` imports `Modules\Media\Contracts\HasMedia`, `Modules\Media\Models\Media`, and `Modules\Media\Traits\InteractsWithMedia`.
- `modules/IAM/app/Http/Requests/V1/UpdateProfileRequest.php` imports `Modules\Media\Rules\AllowedFileName`.
- These four are production imports, not test-only references.
- `modules/IAM/module.json` declares `"requires": ["Media"]`.
- The dependency is one-way: no production file under `modules/Media` imports or names `Modules\IAM`.

The architecture test named `modules should be isolated` in `tests/Architecture/ArchitectureTest.php` allows `Modules\*\*` in its `toOnlyBeUsedIn` list, so it does not forbid one module from using another. What the suite actually enforces:

1. The internal layers `Actions`, `Services`, `Payloads`, `Support`, `Builders`, and `Enums` are not referenced by another module (test `module internal layers are not referenced by other modules`).
2. A cross-module reference must be declared in the consumer's `module.json` `requires` field (test `module dependencies are declared in module.json`).
3. Declared dependencies must be installed, enabled, and acyclic (test `module dependencies pass validation (installed, enabled, acyclic)`, implemented by `App\Support\Modules\ModuleDependencyCheck`).

`config/modules.php` defines `core` as `['IAM', 'Media']`.

No file under `app/` imports or names the `Modules\IAM` or `Modules\Media` namespaces. The `Modules\` matches in `app/` are `Nwidart\Modules\...` (the vendor package) and `App\Support\Modules\...` (an app namespace), not module imports. The direction observed in production code is module to `app/`.

## Decision

1. A module may depend on another module only when it declares that dependency in `module.json` `requires`, and the resulting graph must be acyclic. Undeclared runtime coupling is a defect.
2. A cross-module reference is allowed only into four public seams: `Contracts`, `Models`, `Traits`, and `Rules`. The seams are the layers a consumer needs in order to integrate (a contract, a model, a trait, a validation rule); the other layers hold implementation that may change freely. Every other layer is internal to its module. Only the six internal layers listed above are enforced by tests today; the remaining layers are convention, not a test gate.
3. Media must not import IAM. The dependency direction is Media <- IAM (IAM depends on Media), which matches the manifests.
4. `App\Contracts\Identity` stays in `app/` so a module can type-hint the authenticated user without importing the IAM model. It is type-hinted by `App\Providers\AppServiceProvider` (`Gate::before`, `VerifyEmail::createUrlUsing`, and the password-reset URL builder), `App\Features\BetaFeature::resolve` (used by tests only), the Media controllers through `#[CurrentUser]`, and `MediaPolicy`. IAM form requests reference it in docblocks only.
5. A type belongs in `app/` only when it is used by two or more modules, or referenced by root wiring (bootstrap, service providers, root migrations, global middleware). Otherwise it belongs to its owning module. Production code in `app/` does not currently depend on a module, and modules depend on `app/`. This is not enforced: the isolation test explicitly allows `App\Providers`, `App\Console`, and `App\Builders` to use modules.

## Consequences

- The known deviations from the `app/` placement rule are `App\Concerns\PasswordValidationRules`, `App\Concerns\ProfileValidationRules`, `App\Http\Requests\BulkActionRequest`, `App\Notifications\ResetPassword`, and `App\Notifications\VerifyEmail`. Each is used only by IAM but lives in `app/`. They stay where they are: moving them has no functional value and would cause churn, following the precedent of ADR-0007.
- Known gap: `modules/Media/routes/V1.php` uses the `active` middleware alias registered by `Modules\IAM\Providers\IAMServiceProvider` and backed by `Modules\IAM\Http\Middleware\EnsureUserIsActive`. That is an undeclared runtime dependency from Media to IAM, and no test catches it. IAM also seeds the Media permissions in `Modules\IAM\Database\Seeders\IAMSeeder`. Fixing both is scheduled with Media stabilization, not decided here.
- The test name `modules should be isolated` is misleading because the test allows module-to-module use. It is not renamed here.
- Enforcing the four public seams as an explicit allow-list is a separate decision.

## Alternatives considered

- Strict isolation through contracts in `app/`: this requires moving `HasMedia`, `InteractsWithMedia`, and `AllowedFileName` into `app/`, changing code in the area being stabilized with no proven need.
- Keeping the "never module to module" claim: it contradicts the code.

## Carry-over from ADR-0004 and ADR-0005

- ADR-0004: `App\Contracts\Identity` remains the user type-hint abstraction in `app/`, it extends `MustVerifyEmail`, and roles continue to be eager-loaded narrowly as `roles:id,name,guard_name` by the IAM controllers.
- ADR-0005: `app/` remains the shared vocabulary and contract layer; cross-cutting contracts, enums, and concerns have a single home there.
- This ADR supersedes ADR-0004 and ADR-0005. Their module-isolation claims are replaced by the rules above; the identity contract and shared-vocabulary decisions remain.

## Corrections

- 2026-11-09: The test `module internal layers are not referenced by other modules` was replaced by `modules only reference other modules through public layers`, which enforces an allow-list of `Contracts`, `Models`, `Traits`, and `Rules` over every other layer using the PHP tokenizer. The test `module dependencies are declared in module.json` now uses the same tokenizer-based detection. Enforcing the four public seams as an explicit allow-list is therefore no longer a separate decision (PR #396). (Original text above is kept as written.)
