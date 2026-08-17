# ADR-0024: Module Registry Allow-List Without Environment Toggle

- Status: Superseded by ADR-0029
- Date: 2026-08-11

## Context

Before this change, every folder under `modules/` was auto-discovered: its service providers were registered by iterating the directory. That made private modules impossible to ship in the public repository without making their code public, and there was no way to ship an installed-but-inactive capability.

The roadmap decision was a Fortify-style activation: a module is opt-in via an explicit list, with no environment variable involved.

## Decision

- `config/modules.php` holds the central registry: a map keyed by the lowercase module alias (e.g. `iam`) whose value is `['active' => bool, 'features' => [...] ]`, following the Laravel Fortify pattern.
- An orchestrator provider, `ModuleLoaderServiceProvider`, reads the registry and registers the service provider of every alias with `active => true`, resolving the folder via a case-insensitive directory scan and guarding each with `class_exists`. Modules not listed (or inactive) are silently off: no providers, migrations, or routes are loaded. Route loading itself is delegated to each module's own provider (see ADR-0008), not to `RouteServiceProvider`.
- The `active` flag defaults to `false`, so every capability is opt-in; the shipped modules are set to `true`.
- There is no `MODULES_*` env override. Activation is a code decision, matching the "Explicit over magic" philosophy and keeping config reviewable in code review.

## Consequences

- Enabling or disabling a module is a one-line change in `config/modules.php`; a shipped module stays inactive until a team explicitly opts in.
- Private modules can live in the repository behind a git-ignore list; they never register when unlisted.
- A new directory under `modules/` is not loaded until it is registered with `active => true`, which is the intended safety property.
- The registry also carries build-time feature toggles per module (`features` array), merged by the base `ModuleServiceProvider` into `config('{alias}.features')` and gated by `FeatureFlagMiddleware`.
- A test locks the default registry to the shipped modules so the config and the directory cannot silently drift.
