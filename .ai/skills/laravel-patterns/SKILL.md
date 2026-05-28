---
name: laravel-patterns
description: "Expert guidance for Modular DDD, Single-Action Controllers, and the Action pattern in Laravel 13+."
metadata:
  version: "1.3.0"
  triggers: "DDD, modules, Single-Action Controller, Action, Orchestrator, Database Transaction"
---

# Laravel Patterns

Implements a robust Modular architecture for Laravel 13+.

## Instructions
- Organize all code into `modules/`.
- Use Single-Action Controllers (`final readonly`).
- Isolate business logic into atomic Actions.
- Wrap all writes in database transactions.
- Refer to `references/modular-architecture.md` for the full structural guide.

## Anti-Patterns
- No Repositories.
- No Multi-action controllers.
- No direct Model access in Controllers.
- No logic in Models (except scopes).
