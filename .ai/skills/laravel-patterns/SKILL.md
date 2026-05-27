---
name: laravel-patterns
description: "Domain-Driven Modular Architecture for Laravel 13+."
metadata:
  version: "1.2.0"
---

# Laravel Patterns

Modular DDD and Action-based architecture.

## Instructions
- Follow the modular structure defined in `references/architecture.md`.
- Controllers MUST be single-action (invokable).
- Business logic MUST reside in Actions.

## Guidelines
- Use `DatabaseManager::transaction()` for writes.
- Inject dependencies via constructor.
