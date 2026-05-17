---
name: api-skill
description: "Encodes opinionated best practices for building REST APIs in Laravel 13+. Enforces Single-Action Controllers, Versioned Payloads, Actions, and RFC 9457 error handling."
---

# API Skill for Laravel

This skill defines the exact patterns and rules for building scalable, reliable, and modern REST APIs in Laravel. All guidance here is prescriptive.

---

## 1. Core Architecture

- **Modular Domain Design**: Every feature belongs to a Module.
- **Reference**: Read [references/architecture.md](references/architecture.md) for module boundaries.

## 2. Code Standards

- **Strict PHP**: Mandatory `declare(strict_types=1)`, `final readonly` classes.
- **Reference**: Read [references/code-quality.md](references/code-quality.md) for coding rules.

## 3. Implementation Patterns

- **Versioning**: Always uppercase `V1`, `V2` etc.
- **Actions**: One class per task. Use Orchestrators for complex logic.
- **Documentation**: Mandatory PHPDoc for Scribe generation.
- **Reference**: Read [references/code-examples.md](references/code-examples.md) for implementations.

## 4. Templates

- **Consistency**: Use templates in `assets/templates/` when generating new components.

---

## Anti-Patterns

- ❌ No Resourceful Controllers.
- ❌ No lowercase `v1` in namespaces or directories.
- ❌ No logic in Controllers or Models.
- ❌ No missing PHPDocs on public methods.
- ❌ No circular dependencies between modules.
- ❌ No bare integer status codes.
