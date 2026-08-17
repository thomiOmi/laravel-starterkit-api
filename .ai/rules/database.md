---
paths:
  - 'database/migrations/**'
  - 'modules/*/database/**'
---

# Database

## Goal

Module-owned schema in `modules/{Module}/database/` (migrations, factories, seeders), loaded by the nWidart base `ModuleServiceProvider` while the module is active.

## Rules

1. Use enum values as column defaults (`$table->string('status')->default(StatusEnum::Pending->value)`); cast the column to the enum in the model
2. Never chain migration commands with `&&` or `;` (identical timestamps)
3. Factory + Seeder for every model
4. Schema changes are a review gate: do not edit `database/` or `modules/*/database` without approval
5. Module seeders run via `php artisan db:seed --class=Modules\{Module}\Database\Seeders\{Name}Seeder` or from `database/seeders/DatabaseSeeder`; seeders must not call other modules' seeders (dependencies are seeded sequentially by the caller, e.g. `MediaSeeder` does not call `IAMSeeder`)
6. Module migration rollback via `php artisan migrate:rollback --path=modules/{Module}/database/migrations` (without `--path`, rollback only affects the last global batch)

## Forbidden

- No schema edits without approval
- No migrations outside modules

## Example

```php
Schema::create('posts', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->string('status')->default(StatusEnum::Pending->value);
    $table->timestamps();
});
```
