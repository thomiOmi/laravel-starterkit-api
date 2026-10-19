# ADR-0042: Tenancy Deferred: Organization Module Removed

- Status: Accepted
- Date: 2026-10-19

## Context

ADR-0023 made tenancy an opt-in `Organization` module wrapping `stancl/tenancy`, and ADR-0025 defined the global-users / organization-membership data model for it. The module shipped as inactive scaffolding only (`app/Providers`, `tests`, `config/tenancy.php`, routes): there was no working organization feature behind it.

Commit `055327b` ("chore: remove custom module system") deleted the module together with the custom module system it was scaffolded around. The kit now ships two modules, `IAM` and `Media`, both core, and no application code depends on the removed module.

Keeping an empty tenancy module would advertise a capability that does not exist and would keep pointing readers of the README, roadmap, PRD, and architecture docs toward a module that is not maintained.

## Decision

Tenancy is deferred: the starter kit does not ship an `Organization` module, and this decision supersedes ADR-0023 and ADR-0025.

- Only `IAM` and `Media` are core modules; `config/modules.php` lists exactly those.
- No tenancy scaffolding or organization contract exists in the kit.
- `stancl/tenancy` stays declared in `composer.json` (with auto-discovery disabled) and is handled by a separate dependency decision. This ADR does not remove the package and does not claim it was removed.
- Multi-tenancy and organization domain modeling return only when a product vertical needs them; that work requires a new ADR that re-decides the module shape and the data model.

## Consequences

- The repository, README, roadmap, PRD, and architecture docs describe only capabilities that exist: IAM and Media.
- `stancl/tenancy` is currently unused. Because auto-discovery is disabled it is inert, but it still carries install weight until the dependency decision lands. Related open issues: #224 (module registry + tenancy core) and #228 (enable multi-DB stancl after MVP 1); this ADR resolves neither.
- Re-introducing tenancy is a re-design, not a re-enable: the previous Organization module, its provider lifecycle, and the ADR-0023/0025 model are no longer in the tree, while their historical context stays in those ADRs and their status pointers.
- The tenancy guardrails in `tests/Architecture/ArchitectureTest.php` (tenant-scoped model classification and the `BelongsToTenant` rule) remain in place, so a future tenancy effort starts inside the existing conventions.
- Revisit when a product vertical or an adopter needs multi-tenant data: write a new ADR that supersedes this one, using ADR-0023 and ADR-0025 for historical context, and decide then whether `stancl/tenancy` is adopted, upgraded, or removed.
