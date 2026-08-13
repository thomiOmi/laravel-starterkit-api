---
paths:
  - 'modules/*/Contracts/**'
  - 'app/Contracts/**'
---

# Contracts

## Goal

Module contracts in `modules/{Module}/Contracts/`; cross-module contracts (shared vocabulary) in `app/Contracts/`. Modules communicate through contracts or public API seams, not by importing other modules' internal classes.

## Rules

1. Modules communicate through contracts or public API seams, not by importing other modules' internal classes
2. `app/Contracts` is only for interfaces used by 2+ modules or by core
3. Eloquent models and contracts are a module's public API seam: they may be imported directly by other modules (existing example: the Media module imports `Modules\IAM\Models\User`, `Role`, `Permission`); internal classes (Actions, Services, Payloads, Support, Builders, Enums) are forbidden

Communication paths between modules (4 paths, most preferred first):

1. Shared vocabulary in `app/`: shared enums, contracts, shared requests, response contracts used by 2+ modules without cross-module imports
2. Public API seam: other modules' models + contracts may be imported directly - data + Eloquent relations (example: `Media::uploadedBy()` imports `Modules\IAM\Models\User`), authorization (`MediaPolicy` type-hints `User` + `App\Enums\PermissionEnum`), seeding (`MediaSeeder` firstOrCreate IAM `Role`/`Permission`)
3. Contracts for cross-module behavior: interfaces in `app/Contracts/` implemented by the owning module and bound in its provider (example: `Identity` abstracts the auth actor)
4. Event pub/sub for loose coupling: shared event classes in `app/Events/` (module A dispatches), listeners in the listening module registered explicitly in `bootModule()` (see modules-structure); global listeners in `app/Listeners` are auto-discovered

When model directly vs interface:

- Data + Eloquent relations: model directly (Eloquent needs concrete classes, `belongsTo(User::class)`); interfaces cannot be used for relations
- Behavior/decoupling/2+ possible implementations: interface in `app/Contracts/` (example: `Identity`)
- Exactly 1 implementation that will never become 2+: model directly is enough; interface = YAGNI

Rule of thumb for base class/interface per layer: only if (1) there is logic executed together, (2) real polymorphism/decoupling is needed, (3) cross-module contract, (4) container binding.

## Forbidden

- No importing internal classes across modules (Actions, Services, Payloads, Support, Builders, Enums); model + contract imports are allowed (public API seam, rule 3)
- No base class/interface for mere "consistency" (structure conventions are enforced by ArchitectureTest, not inheritance)
