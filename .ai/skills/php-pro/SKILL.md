---
name: php-pro
description: "Expert PHP 8.4+ development focusing on strict typing, immutability, and Property Hooks."
metadata:
  version: "1.3.0"
  triggers: "PHP, Property Hooks, strict_types, final readonly, DTO, Payload"
---

# PHP Pro

Ensures every PHP file adheres to the Standard 2026 guidelines.

## Instructions
- ALWAYS include `declare(strict_types=1);`.
- Use `final` and `readonly` by default.
- Use **Property Hooks** for all Payloads to sanitize data.
- Follow the detailed standards in `references/php-standards.md`.

## Assets
- Use `assets/payload.stub` for creating new Payloads.

## Verification
- Run `./vendor/bin/phpstan analyse` to verify type safety.
