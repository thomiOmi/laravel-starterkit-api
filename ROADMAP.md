# Project Roadmap: Industrial-Ready Modular Monolith

**Status**: Active
**Core Philosophy**: Configuration-First, Modular, Enforced Architecture.
**Goal**: To build a robust, scalable Laravel starterkit that enforces industrial standards through automated enforcement and configuration.

---

## Phases Overview

| Phase | Title | Focus Area | Status |
| :--- | :--- | :--- | :--- |
| **01** | **Constitution** | Define Architecture Standards | [x] |
| **02** | **Core Refactor** | Generator & Trait Alignment | [ ] |
| **03** | **Enforcement** | Architecture Testing (ArchTest) | [ ] |
| **04** | **Documentation** | API Contract Automation | [ ] |
| **05** | **Observability** | Real-time Monitoring | [ ] |
| **06** | **Ecosystem** | Modular Packaging (Extras) | [ ] |

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
- [ ] Refactor `HasDefaultBehavior.php` to read from `architecture.php`.
- [ ] Update `MakeModule.php` command to inject configs based on `architecture.php`.
- [ ] Update stubs to respect architectural flags.

### Phase 03: Enforcement
*Goal: Automated architecture guarding.*
- [ ] Install `pestphp/pest-plugin-arch`.
- [ ] Write `ArchTest` to prevent cross-module coupling.
- [ ] Write `ArchTest` to enforce Controller/Repository layering.

### Phase 04: Documentation
*Goal: "Write once, document forever".*
- [ ] Integrate `dedoc/scramble`.
- [ ] Configure automatic route/schema scanning.
- [ ] Ensure `MakeModule` output is Scramble-compatible.

### Phase 05: Observability
*Goal: Production-ready health monitoring.*
- [ ] Integrate `laravel/pulse`.
- [ ] Configure slow-query and slow-route tracking.
- [ ] Set up alerts/thresholds in `architecture.php` (if applicable).

### Phase 06: Ecosystem
*Goal: Clean module separation.*
- [ ] Structure `modules/Extras` directory.
- [ ] Migrate non-core features to the Extras directory.
- [ ] Finalize the "Minimal Core" installation process.