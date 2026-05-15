---
name: api-skill
description: "Encodes enterprise-grade patterns for building REST APIs in Laravel 13+. Enforces Single-Action Controllers, Payloads, and RFC 9457 error handling."
---

# API Skill for Laravel

This skill defines the exact patterns and rules for building scalable, reliable, and modern REST APIs in Laravel. All guidance here is prescriptive. When in doubt, follow the rule.

## Core Topics Covered

1.  **Modular Routing**: All routes are versioned and live within modules.
2.  **Single-Action Controllers**: Final invokable classes only.
3.  **Payloads**: Typed data transfer objects replacing traditional DTOs.
4.  **Action Pattern**: Business logic resides in Actions; Repositories are discouraged.
5.  **RFC 9457 Error Handling**: Standardized "Problem Details" JSON responses.
6.  **Spatie Permissions**: RBAC standards for authorization.
7.  **Testing**: Pest PHP for outside-in feature testing.
8.  **Code Quality**: Strict typing, final classes, and PHP 8.4+ standards.

## Usage

AI agents should read the detailed documentation in the `references/` directory upfront to ensure generated code perfectly aligns with these standards.

Start with the index: [references/00-index.md](references/00-index.md).

## Prohibited Patterns (Summary)

- ❌ No Resourceful or Multi-method Controllers.
- ❌ No DTO suffix (use Payload instead).
- ❌ No `paginate()` on API routes (use `simplePaginate()`).
- ❌ No manual `app()` or `resolve()` calls (use constructor injection).
- ❌ No logic in Models or Controllers.
- ❌ No unthrottled routes.
