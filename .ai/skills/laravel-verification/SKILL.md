---
name: laravel-verification
description: "Rigorous verification loop ensuring code formatting, static analysis, and architectural integrity."
metadata:
  version: "1.3.0"
  triggers: "Verification, Pint, PHPStan, Pest, Architecture Test, CI/CD"
---

# Laravel Verification

Guarantees the quality and consistency of the codebase.

## Instructions
- Run Pint with `--format agent`.
- Maintain PHPStan Level 9 compatibility without ignores.
- Write Pest tests for every change.
- Enforce architecture rules via Pest Arch.
- Follow the sequence in `references/quality-assurance.md`.

## Quality Gates
- No `env()` in code.
- No direct Model access in Controllers.
- All classes marked `final`.
