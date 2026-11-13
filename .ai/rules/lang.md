---
paths:
  - 'lang/**'
  - 'modules/*/lang/**'
---

# Localization

## Goal

User-facing strings come from translation files via `__()`, never hardcoded. App translations live in `lang/{locale}/`; module translations in `modules/{Module}/lang/{locale}/` are loaded by the nWidart base `ModuleServiceProvider` under the module alias namespace while the module is active.

## Rules

1. Every key must exist in both `lang/en/` and `lang/id/` with the same key set (parity); new keys are added to both locales. This parity is a convention: no test or tooling compares the two key sets; phpstan `checkMissingTranslations` only checks that used keys exist.
2. Keys are snake_case (`resource_retrieved`, `action_forbidden`); grouped by file: `auth`, `enums`, `general`, `pagination`, `passwords`, `validation`
3. Enum human-readable labels live in `enums.php` under the enum basename (`UserStatusEnum` => values), consumed by the enum's `label()` via `__('enums.'.basename(self::class).'.'.$this->value)`
4. Placeholders use `:name` syntax (`:resource retrieved successfully`); bulk action strings interpolate resource and action (`:resource :action successfully`)
5. Error messages in exceptions and `abort*` use `__()` keys, not raw strings
6. Module lang files load under the module alias namespace (`iam::auth.login_success`), never the default namespace. Keys the framework consumes without a namespace (`auth.failed`, `auth.password`, `auth.throttle`, `passwords.*`) live in the root `lang/`; keys used by `app/`, `bootstrap/`, or across modules also live in the root so call sites stay unnamespaced. Module-only keys stay in the module and are called as `module::group.key`. A root file for a group always wins for unnamespaced lookups; a module file never overrides the root.

## Forbidden

- No hardcoded user-facing strings in controllers, actions, or exceptions
- No locale-specific keys in `en` only (parity is mandatory but convention-only, see rule 1)