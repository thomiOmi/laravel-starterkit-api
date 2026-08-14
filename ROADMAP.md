# Product Roadmap: laravel-starterkit-api

## 1. Executive Summary and Vision

* Vision Statement: A production-ready Laravel API starterkit that is modular, maintainable, and deliberately not overengineered, enabling adopters to fork or clone the repository and build a product vertical without fighting infrastructure concerns.
* Target Audience: Kit adopters (backend and full-stack developers) who fork or clone the repository to bootstrap production API products, and application owners who manage users, roles, permissions, and feature flags.
* Documentation: [Docs](docs/) | Changelog: [Changelog](CHANGELOG.md)

## 2. Status and High-Level Health

* Current Version: v0.19.1
* Release Cadence: Minor releases monthly, Major releases bi-annually.
* Development Status: Active

## 3. Global Status Tag Definitions

* [Done]: Feature is fully tested, merged, and deployed to production.
* [In Progress]: Feature is currently being coded or undergoing code review.
* [Design Phase]: UI/UX wireframing or architecture scoping is underway.
* [Planned]: Feature is approved and scheduled for development in this horizon.
* [Backlog]: Feature is gathered from feedback but not yet scheduled.
* [Blocked]: Development is stopped due to technical or business dependencies.

## 4. Product Horizons

### Horizon 1: Now (Q3 2026) - Focus: Core and Infrastructure Stabilization (MVP 1)

Target: Complete the core and infrastructure gate sequence (rules coverage, native-first, wrapper classification, toolkit items, module consistency, feature toggles) and reach release readiness for tag v1.0.0.

* [Done] P0: Roadmap and Execution Tracker
    * Description: Define the v1.0.0 gate sequence and phase workflow in ROADMAP.md and maintain the daily execution tracker in TASKS.md.
    * Issue Reference:
    * Impact: Gives the project a single source of truth for scope and priorities.
    * Dependencies: None

* [Done] P1: Rules Coverage (G5)
    * Description: Cover every file type in the repository with structured rules (23 rule files), record ADR-0026 and revise ADR-0007, align architecture docs, and enforce conventions via ArchitectureTest.
    * Issue Reference:
    * Impact: Enforces consistent, reviewable conventions across all modules and reduces friction for contributors and AI-assisted development.
    * Dependencies: P0

* [Planned] P2: Native-First (G4)
    * Description: Audit every customization to prove the native Laravel path still works and document escape hatches.
    * Issue Reference:
    * Impact: Adopters can always fall back to standard Laravel behavior without fighting the kit.
    * Dependencies: P1

* [Planned] P3: Wrapper Classification (G2)
    * Description: Classify every first-party wrapper (keep, simplify, or remove) and record each decision in an ADR.
    * Issue Reference:
    * Impact: Removes over-engineering and keeps the codebase lean and maintainable.
    * Dependencies: P2

* [Planned] P4: Toolkit Items (G6)
    * Description: Tidy and test the ready-to-use production toolkit: Sunset headers, idempotency, trace-id, security headers, rate-limit headers, and typed config access.
    * Issue Reference:
    * Impact: Every advertised kit capability is proven by tests and safe to enable in production.
    * Dependencies: P3

* [Planned] P5: Module Consistency (G1)
    * Description: Align all modules with the standard folder matrix (required and optional folders) and move module-specific enums into their owning module.
    * Issue Reference:
    * Impact: Uniform module structure makes the kit predictable to extend.
    * Dependencies: P1

* [Planned] P6: Feature Toggles (G3)
    * Description: Prove every off-able capability is inert by default with tests and add a Pennant feature flag example in app/Features.
    * Issue Reference:
    * Impact: Teams can ship capabilities dormant and activate them deliberately.
    * Dependencies: P5

### Horizon 2: Next (Q4 2026) - Focus: Documentation, Operations, and Release

Target: Complete technical documentation and operations, then ship tag v1.0.0.

* [Planned] P7: Technical Documentation (G7)
    * Description: Rewrite the technical documentation (architecture, API standard, auth, RBAC, rate limiting, testing, module generator) to match the final code.
    * Issue Reference:
    * Impact: The kit is easy to adopt, configure, and extend for new teams.
    * Dependencies: P6

* [Planned] P8: Operations (G8)
    * Description: Add the health endpoint (module System), the deployment guide, and record the Scramble (OpenAPI) decision.
    * Issue Reference:
    * Impact: Operators can monitor service health and deploy with confidence.
    * Dependencies: P7

* [Planned] P9: Release v1.0.0 (G9)
    * Description: Update the changelog, verify the release workflow, and tag v1.0.0 with CI green on PHP 8.4 and 8.5.
    * Issue Reference:
    * Impact: Marks the kit as stable and production-ready for adopters.
    * Dependencies: P8

### Horizon 3: Later (2027) - Focus: SaaS Vertical

Target: Long-term strategic goals subject to change based on market research.

* [Backlog] SaaS Vertical: Invoicing Module
    * Description: Ship the first SaaS vertical: an invoicing module with PPN (Indonesian VAT) designed into the schema from the start.
    * Strategic Goal: Validate the starterkit as the foundation for a provider program serving multiple customer organizations.

* [Backlog] Organization Domain Model and Membership
    * Description: Introduce the organization domain model, membership relations, and per-organization roles on top of the Organization tenancy module.
    * Strategic Goal: Enable multi-organization product scenarios (one identity, membership-based access) that the SaaS vertical depends on.

## 5. Recent Shipments (Past 2 Quarters)

* [Done] v0.19.1 (Q3 2026): IAM performance improvements with eager loading in user create and update actions.
* [Done] Phase A: Repository Health (Q3 2026): LICENSE, README overhaul, CONTRIBUTING, SECURITY policy, CODE_OF_CONDUCT, issue and pull request templates, GitHub Projects roadmap, and branch protection on main.
* [Done] Phase B: Modular Architecture and Tenancy (Q3 2026): Fortify-style module registry (config/modules.php), Organization module wrapping stancl/tenancy v3.10 (single database, disabled by default), ADR-0023 to ADR-0025, and PRD v2.

## 6. Contribution and Feedback

We practice open product management. If you want to influence our roadmap:

1. Review our Feature Request Guidelines in [CONTRIBUTING.md](./CONTRIBUTING.md).
2. Open a GitHub Discussion to propose new ideas.
3. Vote on existing features using reactions in the Issue Tracker.

Disclaimer: This roadmap is for informational purposes only. Priorities may shift based on user feedback and market conditions.
