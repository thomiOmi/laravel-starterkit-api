# ADR-0027: Module Anatomy Migration Timing and API V2 Versioning

- Status: Accepted
- Date: 2026-08-14

## Context

The architecture document (`docs/architecture.md`) section 3 defines the target module anatomy where HTTP layers live in a `Http/` container (`Http/Controllers`, `Http/Requests`, `Http/Resources`) and CLI layers in `Console/Commands`, mirroring the stock Laravel `app/` skeleton. The rules system (`.ai/rules/modules-structure.md`) and the base `ModuleServiceProvider` (which registers commands from `Console/Commands`) already follow this target anatomy.

The existing modules (IAM, Media) still use the legacy flat anatomy (`Controllers/`, `Requests/`, `Resources/` at the module root), and the module generator (`MakeModuleCommand`), its stubs, the architecture tests, and the module documentation still emit and assert the legacy paths. Migrating the existing modules is a breaking change touching namespaces, files, the generator, stubs, tests, and documentation.

Separately, the anatomy mentions versioned layers (`V1/`, `V2/` in Controllers/Requests, `Routes/V2.php`) but no V2 use case exists yet, and the versioning mechanism (URL prefix vs header, V1 sunset policy) is undefined. These were open questions in `docs/architecture.md` section 10.

## Decision

### Q1: Module anatomy migration executes in P5 (G1), not in this document's review

Migrating IAM and Media to the `Http/` container anatomy (`Http/Controllers`, `Http/Requests`, `Http/Resources`, `Console/Commands`) is a breaking change and is scheduled for phase P5 (Module Consistency, gate G1), which owns "align all modules with the standard folder matrix". It is not part of the architecture document review cycle. The migration includes:

- moving module folders and updating namespaces in all affected files,
- updating the module generator (`MakeModuleCommand`) and its stubs,
- updating the architecture tests that assert `Modules\*\Controllers`,
- updating the module documentation (skill, module generator doc) to match,
- full verification (lint, rector, phpstan, test suite, architecture tests).

### Q2: API V2 mechanism deferred until the first V2 use case

The V2 versioning mechanism (URL prefix `api/v2` vs header negotiation) and the V1 sunset policy are deferred until the first V2 use case appears. The kit does not pick a mechanism without a real consumer ("production-ready, not overengineered"). The structural support already exists: the generator accepts a version parameter, the base provider loads `Routes/V{version}.php` per module, and the `Sunset` middleware (RFC 8594 deprecation header) is available for announcing V1 deprecation when the time comes. When a V2 use case appears, record the mechanism decision in a new ADR before implementing it.

## Consequences

- Easier: reviewers and contributors have one source of truth for the target anatomy; the migration is a contained, verifiable work item in P5 instead of a hidden breaking change.
- Easier: module anatomy decisions do not block the architecture document or earlier gates.
- More difficult: until P5 runs, modules, generator, and docs stay temporarily inconsistent with the target anatomy - the documented target is ahead of the implementation. This is accepted because the migration is deliberate and scheduled.
- More difficult: V2 clients cannot be planned until a use case exists; the kit remains V1-only, which matches the "no speculative generality" principle.