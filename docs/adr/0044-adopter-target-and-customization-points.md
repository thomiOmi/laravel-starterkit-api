# ADR-0044: Adopter Target and Customization Points

- Status: Proposed
- Date: 2026-10-26

## Context

The PRD and roadmap describe adopters who fork or clone the kit to build a product vertical. Nothing records which parts an adopter is expected to change, which parts the kit keeps stable, or whether an adopter can pull later upstream changes.

The kit is inspired by extensible platforms such as ERPNext, Odoo, and WordPress, where a stable core is separated from customizations and upgrades are supported. Those platforms depend on a stable extension API, a deprecation policy, and a tested upgrade path, maintained by teams over many years. This kit has one maintainer, two core modules (IAM and Media), and no external adopter yet.

ADR-0042 deferred tenancy until a product vertical or an adopter needs multi-tenant data. The first planned vertical is Invoicing (issue #226).

## Decision

1. Adoption model: copy once. After a fork or clone, the code belongs to the adopter. The kit does not promise upgrades that merge cleanly from upstream.
2. The kit keeps a small stable surface for a major release line (pre-1.0 today):
   - Contracts in `app/Contracts/` (today: `Identity`, ADR-0004).
   - Module anatomy and activation as documented in ADR-0029 and `docs/architecture.md` (status in `modules_statuses.json`, managed with `module:enable` / `module:disable`).
   - Per-module feature flags (ADR-0022).
   - Versioned API routes (`/api/v1/...`) and the response envelope documented in `docs/architecture.md` and `docs/api-standard.md`.
3. Intended customization points, which adopters use without editing core module internals:
   - Add a module under `modules/` and enable it with `php artisan module:enable` (status in `modules_statuses.json`; ADR-0029).
   - Replace the user model with one that implements the `Identity` contract (ADR-0004).
   - Toggle module features through `config('{alias}.features.{feature}')` and Pennant, gated by the `feature-flag` middleware (ADR-0022).
   - Configure Media storage disks through `disk`, `private_disk`, and `conversions_disk_name` in `modules/Media/config/config.php`.
4. Outside the stable surface: internals of the core modules (actions, builders, policies, migrations) may change between releases. An adopter who edits them owns the cost of porting upstream changes by hand. Changes are recorded in `CHANGELOG.md`; security fixes must be called out there so adopters can apply them by hand.
5. Packaging the core modules as versioned Composer packages, or any upstream-following platform model, is not decided here. Revisit it when the first vertical exists and shows how often it must touch core; that decision requires a new ADR that supersedes this one.

## Consequences

- The cost of maintaining the kit stays proportional to one maintainer, and the promise to adopters is small and checkable.
- Adopters who modify core internals will not get clean upstream updates; this is stated up front instead of discovered later.
- Contracts and routes in the stable surface cannot change silently within a major version; changes to them need a `CHANGELOG.md` entry, and changes to `tests/Architecture/ArchitectureTest.php` need explicit human approval.
- Domain event coverage is partial: IAM dispatches `Illuminate\Auth\Events\Registered` on registration, and the Media module dispatches `MediaUploaded`, `MediaCreated`, `MediaProcessed`, and `MediaProcessingFailed`. A user-logged-in event does not exist yet.
- Async reactions to core behavior are expected to use domain events, so a vertical can react without editing core. This ADR stays Proposed until a user-logged-in event exists.
- It becomes Accepted when the first vertical module is built against the stable surface without editing core module internals. If that attempt shows the surface must change, this ADR is revised first.
