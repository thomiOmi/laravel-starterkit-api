# Project Roadmap: Industrial-Ready Modular Monolith

**Status**: Active
**Core Philosophy**: Configuration-First, Modular, Enforced Architecture.
**Goal**: To build a robust, scalable Laravel starterkit that enforces industrial standards through automated enforcement and configuration.

---

## Phases Overview

| Phase | Title | Focus Area | Status |
| :--- | :--- | :--- | :--- |
| **01** | **Constitution** | Define Architecture Standards | [x] |
| **02** | **Core Refactor** | Generator & Trait Alignment | [x] |
| **03** | **Enforcement** | Architecture Testing (ArchTest) | [x] |
| **04** | **Observability** | Real-time Monitoring | [ ] |
| **05** | **Ecosystem** | Modular Packaging (Extras) | [ ] |
| **06** | **Documentation** | API Contract Automation | [ ] |

---

## Detailed Roadmap

### Phase 01: The Constitution
*Goal: Centralize architectural decisions.*
- [x] Create `config/architecture.php`.
- [x] Define `model.default_id` strategy (ULID/UUID/Integer).
- [x] Define global behaviors (SoftDeletes, Auditing flags).
- [x] Define `naming` conventions for Actions/Repositories.

### Phase 02: Core Refactor
*Goal: Align system generators with the Constitution.*
- [x] Refactor `HasDefaultBehavior.php` to read from `architecture.php`.
- [x] Update `MakeModule.php` command to inject configs based on `architecture.php`.
- [x] Update stubs to respect architectural flags.

### Phase 03: Enforcement
*Goal: Automated architecture guarding.*
- [x] Install `pestphp/pest-plugin-arch` (built-in Pest 4).
- [x] Write `ArchTest` to prevent cross-module coupling (module isolation).
- [x] Write `ArchTest` to enforce Controller/Repository layering (via existing controller/action/payload/payloads rules).
- [x] All `ArchTest` patterns use `config('architecture.module.base_path')` as the single source of truth for module location.

### Phase 04: Observability
*Goal: Production-ready health monitoring.*
- [ ] Integrate `laravel/pulse`.
- [ ] Configure slow-query and slow-route tracking.
- [ ] Set up alerts/thresholds in `architecture.php` (if applicable).

### Phase 04b: CI & Test Infrastructure
*Goal: Fast, reliable CI with production-like services.*
- [ ] Investigate Spatie Permission cache race condition in parallel CI tests.
- [ ] Re-introduce MySQL and Redis services in CI with proper per-worker isolation (separate databases, unique cache prefixes).
- [ ] Evaluate `TEST_TOKEN`-based cache/session prefixing for shared services.
- [ ] Document findings in KNOWLEDGE.md and ROADMAP.md.

### Phase 05: Ecosystem
*Goal: Clean module separation.*
- [ ] Structure `modules/Extras` directory.
- [ ] Migrate non-core features to the Extras directory.
- [ ] Finalize the "Minimal Core" installation process.

### Phase 06: Documentation & Type Discovery
*Goal: Automated API documentation with 0-boilerplate.*
- [ ] Install & Configure `dedoc/scramble`.
- [ ] Investigate & Fix missing schema detection (Deep-dive into Reflection/Type detection).
- [ ] Implement "Auto-discovery helpers" (Transformers/Extenders) to minimize manual attributes.