# Architecture Testing (Pest Arch)

Architecture Testing is mandatory to automatically maintain 2026 standards.

---

## 1. Global Rules

Every developer must ensure these rules pass in `tests/Feature/ArchitectureTest.php`.

```php
arch('strict types must be used')
    ->expect(['App', 'Modules'])
    ->toUseStrictTypes();

arch('avoid debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
```

## 2. Structural Rules

### Controllers

- Must be `final` and `readonly`.
- Prohibited from accessing Models or the DB directly (must go through an Action).
- Use PHP Attributes for documentation.

```php
arch('controllers must be final and readonly')
    ->expect('Modules\*\Controllers')
    ->toBeFinal()
    ->toBeReadonly();

arch('controllers must not access models directly')
    ->expect('Modules\*\Controllers')
    ->not->toUse('Modules\*\Models');
```

### Actions

- Must be `final` and `readonly`.
- The sole location for business logic and DB transactions.

```php
arch('actions must be final and readonly')
    ->expect('Modules\*\Actions')
    ->toBeFinal()
    ->toBeReadonly();
```

### Payloads

- Must be `final` and `readonly`.
- Primary location for PHP 8.4 Property Hooks usage.

```php
arch('payloads must be final and readonly')
    ->expect('Modules\*\Payloads')
    ->toBeFinal()
    ->toBeReadonly();
```

## 3. Modular Boundaries

Modules must be independent for state-changing operations.

```php
arch('modules must not use actions from other modules')
    ->expect('Modules\ModuleA\Actions')
    ->not->toUse('Modules\ModuleB\Actions');
```

*Note: Cross-module Model access is permitted only for read-only data retrieval.*
