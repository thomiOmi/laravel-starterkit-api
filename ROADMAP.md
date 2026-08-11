# Roadmap: laravel-starterkit-api

## Vision

A production-ready Laravel API starterkit that is modular, maintainable, and deliberately not overengineered. Adopters fork or clone the repository and build a product vertical without fighting infrastructure concerns.

## MVP Definition

| MVP | Scope | Target |
|-----|-------|--------|
| MVP 1 | Core and infrastructure: rules, module consistency, toggles, toolkit items, ops | Stable release tag `v1.0.0` |
| MVP 2 | SaaS vertical: Invoicing module (PPN Indonesia from schema design), organization domain model and membership | Post `v1.0.0` |

## Definition of v1.0.0 (Gates)

Execution order is fixed. Each gate has an exit criterion proven by code and tests, not by intention.

| # | Gate | Exit Criterion |
|---|------|----------------|
| G5 | Rules coverage | `.ai/rules/` covers every file type in the repo; architecture docs match the rules; ArchitectureTest enforces them |
| G4 | Native-first | Every customization keeps the native Laravel path working; escape hatches proven by tests |
| G2 | Wrapper classification | Every wrapper classified (keep / simplify / remove); each decision recorded in an ADR |
| G6 | Toolkit items (code) | Sunset, idempotency, trace-id, security headers, rate-limit headers, typed config: each tidy and tested |
| G1 | Module consistency | All modules follow the folder matrix; deliberate deviations recorded |
| G3 | Feature toggles | Every off-able capability is proven inert by tests; Pennant example in `app/Features` |
| G7 | Technical documentation | docs/*.md match the final code |
| G8 | Operations | Health endpoint, deployment guide, Scramble decision recorded |
| G9 | Release | CI green (PHP 8.4 / 8.5), CHANGELOG updated, tag `v1.0.0` |

## Phases

| Phase | Gate | Deliverable | Status |
|-------|------|-------------|--------|
| P0 | - | ROADMAP.md + TASKS.md | In progress |
| P1 | G5 | 23 rule files (standard format), ADR-0026, ADR-0007 revision, architecture docs, ArchitectureTest additions | Pending |
| P2 | G4 | Native-first audit + escape hatches + tests | Pending |
| P3 | G2 | Wrapper pass (per item ADR + code change) | Pending |
| P4 | G6 | Toolkit items code cleanup + tests | Pending |
| P5 | G1 | Module folder consistency (enum move, layout alignment) | Pending |
| P6 | G3 | Feature toggle example + inert-by-default tests | Pending |
| P7 | G7 | Technical documentation pass | Pending |
| P8 | G8 | Health endpoint, deployment guide, Scramble decision | Pending |
| P9 | G9 | CHANGELOG, release workflow, tag `v1.0.0` | Pending |

## Phase Workflow

Every phase follows the same loop:

1. Research and confirm scope (with user review)
2. Implement
3. Test (new or updated tests for every change)
4. Record rules in `.ai/rules/` via `record-rule`
5. Run quality gates: `composer lint`, `composer types:check`, `composer test:quality`, `composer ci:check`
6. Stop and present the diff for user review (before any push)
7. On approval: push, open a pull request, wait for CI
8. Update this roadmap status table

## Document Relations

| Document | Role |
|----------|------|
| [docs/prd/](docs/prd/) | What the product needs (PRD, requirements) |
| [docs/adr/](docs/adr/) | Why a convention exists (decisions) |
| [docs/](docs/README.md) | How to use and extend (technical) |
| [TASKS.md](TASKS.md) | Daily execution tracker (not committed, gitignored) |
