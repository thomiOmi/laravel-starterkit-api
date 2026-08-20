---
paths:
  - 'lang/**'
  - 'modules/*/lang/**'
---

# Localization

## Goal

User-facing strings come from translation files via `__()`, never hardcoded. App translations live in `lang/{locale}/`; module translations in `modules/{Module}/lang/{locale}/` are loaded by the nWidart base `ModuleServiceProvider` while the module is active.

## Rules

1. Every key must exist in both `lang/en/` and `lang/id/` with the same key set (parity); new keys are added to both locales
2. Keys are snake_case (`resource_retrieved`, `action_forbidden`); grouped by file: `auth`, `enums`, `general`, `pagination`, `passwords`, `validation`
3. Enum human-readable labels live in `enums.php` under the enum basename (`UserStatusEnum` => values), consumed by the enum's `label()` via `__('enums.'.basename(self::class).'.'.$this->value)`
4. Placeholders use `:name` syntax (`:resource retrieved successfully`); bulk action strings interpolate resource and action (`:resource :action successfully`)
5. Error messages in exceptions and `abort*` use `__()` keys, not raw strings
6. Module lang files load under the DEFAULT namespace (no namespace is registered), so a module file with the same name overrides the root `lang/` file for that group (`auth`, `passwords`). Keep IAM-owned groups in the module and app-shared groups (`enums`, `general`, `pagination`, `validation`) at the root.

## Forbidden

- No hardcoded user-facing strings in controllers, actions, or exceptions
- No locale-specific keys in `en` only (parity is mandatory)