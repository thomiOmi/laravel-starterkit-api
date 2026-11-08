---
paths:
  - 'modules/*/app/Contracts/**'
  - 'app/Contracts/**'
---

# Contracts

## Goal

Module contracts in `modules/{Module}/app/Contracts/`; cross-module contracts (shared vocabulary) in `app/Contracts/`. A module may depend on another module only when it declares the dependency in `module.json` `requires`, and the graph must be acyclic (ADR-0045). Cross-module references are allowed only to the public seams `Contracts`, `Models`, `Traits`, and `Rules`; every other layer is internal.

## Rules

1. A module may depend on another module only when it declares the dependency in `module.json` `requires`, and the graph must be acyclic (ADR-0045). Cross-module references are allowed only to the public seams `Contracts`, `Models`, `Traits`, and `Rules`; every other layer is internal. Tests enforce that the layers Actions, Services, Payloads, Support, Builders, and Enums are not referenced across modules, and that dependencies are declared; the seam list itself is a convention. Media must not import IAM. Today IAM requires Media.
2. `app/Contracts` is only for interfaces used by 2+ modules or by core
3. Eloquent models, contracts, traits, and rules are a module's public API seam: they may be imported directly by other modules. Today IAM imports `Modules\Media\Contracts\HasMedia`, `Modules\Media\Models\Media`, `Modules\Media\Traits\InteractsWithMedia`, and `Modules\Media\Rules\AllowedFileName`; internal classes (Actions, Services, Payloads, Support, Builders, Enums) are forbidden

Communication paths between modules (4 paths, most preferred first):

1. Shared vocabulary in `app/`: shared enums, contracts, shared requests, response contracts used by 2+ modules without cross-module imports
2. Public API seam: other modules' contracts, models, traits, and rules may be imported directly - data + Eloquent relations (example: `Modules\IAM\Models\User` implements `Modules\Media\Contracts\HasMedia` and uses `Modules\Media\Traits\InteractsWithMedia`), validation (`Modules\IAM\Http\Requests\V1\UpdateProfileRequest` uses `Modules\Media\Rules\AllowedFileName`)
3. Contracts for cross-module behavior: interfaces in `app/Contracts/` implemented by the owning module and bound in its provider (example: `Identity` abstracts the auth actor)
4. Event pub/sub for loose coupling: shared event classes in `app/Events/` (module A dispatches), listeners in the listening module registered explicitly in `bootModule()` (see modules-structure); global listeners in `app/Listeners` are auto-discovered

When model directly vs interface:

- Data + Eloquent relations: model directly (Eloquent needs concrete classes, `belongsTo(User::class)`); interfaces cannot be used for relations
- Behavior/decoupling/2+ possible implementations: interface in `app/Contracts/` (example: `Identity`)
- Exactly 1 implementation that will never become 2+: model directly is enough; interface = YAGNI

Rule of thumb for base class/interface per layer: only if (1) there is logic executed together, (2) real polymorphism/decoupling is needed, (3) cross-module contract, (4) container binding.

## Forbidden

- No importing internal classes across modules (Actions, Services, Payloads, Support, Builders, Enums); contract, model, trait, and rule imports are allowed (public API seam, rule 3)
- No base class/interface for mere "consistency" (structure conventions are enforced by ArchitectureTest, not inheritance)

## Cross-module ports: capability contracts in the shared kernel
Cross-module BEHAVIOUR uses interfaces in `app/Contracts/` (the current shared example is `Identity`, implemented by the IAM `User` model). A capability contract is implemented by the providing module and bound in its ServiceProvider; consumers inject optional capabilities as nullable (`?Contract $dep = null`) so an absent module degrades gracefully, and pass scope constraints (collection, etc.) as explicit arguments instead of hardcoding them in the port. One port per capability — generalize only when a second real consumer exists. Enums shared across modules belong in App\Enums. The module.json requires field is for STRUCTURAL dependencies only (models/factories); never declare bidirectional requires — the cycle detector rejects it. Data access uses the model public seam via requires; behaviour uses these ports; async reactions use events.
