# ADR-0006: Module Policies Live in Modules, Registered via Gate::policy

- Status: Accepted
- Date: 2026-08-08

## Context

Policies are bound to module models (e.g. `Modules\IAM\Models\User`). `App\Policies` is not in the architecture-test allowlist, and Laravel's auto-discovery only covers `App\Models`.

## Decision

Policies for module models live in the owning module (`Modules/{Module}/Policies`) and are registered explicitly via `Gate::policy()` in the module's service provider (`configurePolicies()`).

## Consequences

- `modules should be isolated` stays green.
- Registration is explicit, not magic: `Gate::policy()` makes the binding visible.
- Form Requests authorize through `$user->can(...)` using config-driven model class names (`config('auth.providers.users.model')`, `config('permission.models.role|permission')`) to avoid importing `Modules\*\Models` in Requests.
