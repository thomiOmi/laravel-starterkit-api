# ADR-0026: Rules Coverage Decisions (G5)

- Status: Accepted
- Date: 2026-08-14

## Context

Gate G5 requires every file type in the repository to be covered by structured conventions. The conventions lived scattered across docs, skills, and the architecture test without a single source of truth. The rules system (`.ai/rules/`) now holds 27 rule files, but four design decisions behind them were never recorded. This ADR records them so future changes stay consistent.

## Decision

### D1: Query filtering is builder-based; there is no Filters layer

All query-string filtering (search, filter, sort, fields, include) goes through `App\Builders\BaseQueryBuilder` subclasses registered on models via `#[UseEloquentBuilder]`, whitelisting `$allowedFilters`, `$allowedSorts`, `$allowedFields`, `$allowedIncludes`, `$searchableColumns`. There is no `Filters/` directory and no `BaseFilter` class; the historic implementation (see ADR-0014) was replaced. On 2026-08-14 the last dead references were removed: the modular-architecture skill (SKILL.md and module template, in both `.ai` and `.agents` trees), `docs/module-generator.md`, the stale dataset row in `docs/testing.md`, and the `Filters` column of the `module:list` command (now `Builders`). Only ADR-0014 mentions `BaseFilter` (historical record).

### D2: Actions vs Services vs Support

- Action: exactly one business use case, `final readonly`, one `handle()` method (`.ai/rules/actions.md`).
- Service: business logic shared by 2+ call sites or consolidating a complex flow across use cases (`.ai/rules/services.md`).
- Support: purely technical utilities, no business state, no Eloquent (`.ai/rules/support.md`).

A single-use-case class is an Action, not a Service; business logic is never Support.

### D3: Enum placement follows ADR-0007 (revised)

Shared vocabulary (consumed by root-level files or 2+ modules) lives in `app/Enums`; single-module enums live in `modules/{Module}/Enums`. See the ADR-0007 revision for the usage audit.

### D4: Module structure mirrors the stock Laravel skeleton

`modules/{Module}/` mirrors `app/`; only `Providers`, `Routes`, `Tests` are required on active modules; every other folder is optional and created only when it contains at least one file (`.ai/rules/modules-structure.md`). Module generator stubs (`resources/stubs/module/`) must stay in sync with these conventions.

## Consequences

- Conventions now have one discoverable home (`.ai/rules/index.md` maps globs to rule files) plus enforcement via the architecture test.
- Contributors and AI agents get consistent guidance; the skill and docs no longer contradict the code.
- The `module:list` output column is `Builders` going forward; any tooling parsing the old `Filters` column must be updated.
- New layers require both a rule file entry and a matching module generator stub (D4), keeping generation and convention in lockstep.