---
name: architecture
description: Project architecture reference covering data flow, module structure, patterns, and service providers. Use when understanding how the codebase is organized or adding new features.
metadata:
  version: "1.0"
  type: reference
---

# Architecture Reference Skill

This skill describes the architecture of the Laravel Starterkit API. Use it to understand data flow, module boundaries, coding patterns, and how to add new features correctly.

## Key Principles

1. Controllers are `final readonly` invokable classes -- no business logic
2. Actions encapsulate single business operations via `handle()`
3. Repositories are read-only (`findById`, `paginate`); writes use Eloquent directly
4. Responses via `JsonResponse` or `ResourceCollection::additional()->response()`
5. Errors via `ProblemResponse` (RFC 9457)

See [the reference guide](references/architecture.md) for full architecture details including data flow diagram, code examples, and exception handling.
