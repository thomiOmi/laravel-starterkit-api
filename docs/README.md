# Documentation

Canonical documentation for the Laravel Starterkit API. This directory is the source of truth for product requirements, decisions, and technical documentation.

## Document Map

| Path | Purpose |
|------|---------|
| [prd/](prd/README.md) | Product requirements: [starterkit-api.md](prd/starterkit-api.md) (main PRD, v1 scope) |
| [adr/](adr/README.md) | Architecture Decision Records: 29 decisions with rationale (Nygard format) |
| [api-standard.md](api-standard.md) | API response contract and envelope shapes |
| [architecture.md](architecture.md) | Module structure and architecture patterns |
| [auth.md](auth.md) | Authentication flows (Sanctum, email verification, password reset) |
| [coding-standards.md](coding-standards.md) | Code style and language conventions |
| [module-generator.md](module-generator.md) | `module:make` usage and module scaffolding |
| [rate-limiting.md](rate-limiting.md) | Throttle configuration on auth routes |
| [rbac.md](rbac.md) | Roles, permissions, policies (Spatie) |
| [testing.md](testing.md) | Testing conventions: helpers, datasets, describe/it/group, TIA, probes |

## Relation Between Documents

```text
PRD (what/why the product needs)      ->  docs/prd/
   decision rationale                 ->  docs/adr/ (why a convention exists)
   execution status                   ->  TASKS.md (phase tracking, not committed decisions)
   technical detail                   ->  docs/*.md (how to use/extend)
```

- Requirements in the PRD carry IDs (AUTH-01, IAM-01, ...). Their shipped status is tracked in the PRD tables and the execution tracker.
- ADRs are the answer to "why is the code like this?" — read them before changing a settled convention.
- `docs/*.md` are the "how" documents — implementation conventions and usage guides.

## Updating

- New feature with product scope: create `docs/prd/{feature}.md` from `docs/prd/template.md` and link it in the PRD README.
- New settled decision: create `docs/adr/NNNN-<slug>.md` from `docs/adr/template.md` and add it to the index table.
- Keep technical docs current when code conventions change; record the rule in `.ai/rules/` via `record-rule` so agents inherit it.
