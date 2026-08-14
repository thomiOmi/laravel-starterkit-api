# Laravel Standard Coding Guidelines

## Tech Stack

| Layer | Tech |
| --- | --- |
| Framework | Laravel 13 + PHP 8.4 |
| Database | MySQL |
| Package manager | Composer 2.9+ |
| Auth | Laravel Sanctum (token-based) |
| Role/Permission | Spatie `laravel-permission` |
| Social Auth | Laravel Socialite |
| Feature Flags | Laravel Pennant |

## Project Philosophy

| Principle | Meaning |
|---|---|
| **Production-ready, not overengineered** | Every abstraction must solve a real problem. Do not build config files, middleware, or services for scenarios that do not exist yet. Prefer inline code with a default over a configurable flag that is never changed. |
| **Laravel-native first** | Use built-in Laravel features before introducing custom code or new dependencies. Convention over configuration is the default — only extract to config when the value changes between environments or is consumed by 3+ call sites. |
| **Module isolation** | Modules communicate through contracts, not through direct imports. Keep the core (IAM) minimal and let feature modules extend it. |
| **Explicit over magic** | Route middleware, authorization, and validation should be visible in the route definition and Form Request, not hidden in service providers or auto-discovery. |
| **Maintainability over cleverness** | Favor flat structures, final readonly classes, and explicit type hints. Code is written once and read many times. |

## API Convention

| Aspect | Detail |
| --- | --- |
| Base URL | `/api/v1/...` (lowercase) |
| Auth | `Authorization: Bearer {token}` (Sanctum) |
| Response | `SuccessResponse` / `ProblemResponse` — `{status, title?, detail?, data, meta?}` (NO `success` boolean) |
| Error | `ProblemResponse` — RFC 9457 |
| Date format | `Y-m-d H:i:s` |
| Route names | `v1.{module}.{name}` |

## Project Rules (`.ai/rules/`)

Area-specific conventions (testing, controllers, actions, models, routes, responses, database) live in `.ai/rules/` and are matched to files by glob. Read the rule files matching your work before editing. When a settled convention is not yet recorded, record it with `record-rule` so the next agent inherits it.

## Quality Gates

- After writing PHP code: `composer lint:staged` (pre-commit) or `composer lint` (all files)
- Then: `composer rector:dry`
- Then: `composer types:check` (phpstan)
- Run tests: `composer test` (pest, unit + feature) and `composer test:profanity`
- Before pushing: `composer ci:check` (runs lint:check -> rector:dry -> types:check -> test -> test:profanity in order)
- Coverage, mutation, and type-coverage gates are temporarily suspended (scripts removed); re-enable once the suite stabilizes
- Fix all errors in code (do NOT modify `phpstan.neon`); do NOT use `@phpstan-ignore` — fix the root cause instead
- Follow existing code conventions — check sibling files and `.ai/rules/` before creating new ones
- Every change must have a corresponding test

## Architecture Test Rules (`tests/Architecture/ArchitectureTest.php`)

- **DO NOT** modify `ArchitectureTest.php` unless explicitly instructed by the user
- If architecture tests fail due to code changes, **report the failure to the user** and let them decide how to proceed — do NOT auto-fix or auto-ignore the rule
- The architecture test file is the project's single source of truth for conventions; changes require deliberate human approval

## Agentic Development (Laravel Boost & Agent Skills)

- **Laravel Boost**: Accelerates development with framework-specific guidelines and MCP tools (`search-docs`, `database-schema`, etc.).
- **Agent Skills**: Uses the [agentskills.io](https://agentskills.io) format for domain-specific expertise.
- **Location**: Guidelines in `.ai/guidelines/`, Skills in `.ai/skills/`.
- **Activation**: Guidelines are loaded upfront; Skills are activated on-demand via triggers (`skill` tool).
- **Update**: Run `php artisan boost:update --discover` to sync/re-apply all guidelines and skills.

## File Ownership Rules

| File/Dir | Managed by | Edit via |
|---|---|---|
| `AGENTS.md` | Laravel Boost (auto-generated) | **DO NOT** edit directly — edit `.ai/guidelines/` or `.ai/skills/` instead |
| `.ai/guidelines/` | You (survives `boost:update`) | Edit directly — source files for AGENTS.md guidelines section |
| `.ai/rules/` | Boost `record-rule` + you | Record via `record-rule`; hand-edits allowed but keep `index.md` in sync |
| `.ai/skills/` | You (survives `boost:update`) | Edit directly — Boost-managed agent skills |
| `.agents/` | AI agent's skill/rules system | **DO NOT** edit directly — let the AI agent manage it |
