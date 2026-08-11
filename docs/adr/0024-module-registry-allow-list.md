# ADR-0024: Module Registry Allow-List Without Environment Toggle

- Status: Accepted
- Date: 2026-08-11

## Context

Before this change, every folder under `modules/` was auto-discovered: its service providers were registered by iterating the directory. That made private modules impossible to ship in the public repository without making their code public, and there was no way to ship an installed-but-inactive capability.

The roadmap decision was a Fortify-style activation: a module is opt-in via an explicit list, with no environment variable involved.

## Decision

- `config/modules.php` holds an allow-list of module directory names (lowercase, e.g. `organization`).
- `ModuleServiceProvider` and `RouteServiceProvider` filter `modules/*` by that list. Modules not listed are silently off: no providers, migrations, or routes are loaded.
- The default list enables every shipped module, so existing behavior is preserved for consumers who never touch the file.
- There is no `MODULES_*` env override. Activation is a code decision, matching the "Explicit over magic" philosophy and keeping config reviewable in code review.

## Consequences

- Enabling or disabling a module is a one-line change in `config/modules.php`; a shipped module stays inactive until a team explicitly opts in.
- Private modules can live in the repository behind a git-ignore list; they never register when unlisted.
- A new directory under `modules/` is not loaded until it is added to the allow-list, which is the intended safety property.
- A test locks the default list to the shipped modules so the config and the directory cannot silently drift.
