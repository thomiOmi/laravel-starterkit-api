---
name: laravel-boost-patterns
description: "Domain-Driven Modular Architecture and Action patterns for Laravel Boost."
metadata:
  version: "1.0.0"
  triggers: "DDD, modules, Single-Action Controllers, Actions, Database Transaction"
---

# Laravel Boost Patterns

Defines the architectural structure and business logic flow.

## Architecture
- **Modules**: All domain logic lives in `modules/{Module}/`.
- **Single-Action Controllers**: Only `__invoke()`. Inject actions via constructor.
- **Action Pattern**: One action per database operation.
- **Transactions**: Wrap all write operations in `$database->transaction()`.

## Directory Structure
- `modules/{Module}/Controllers/V1/`
- `modules/{Module}/Actions/`
- `modules/{Module}/Payloads/V1/`
- `modules/{Module}/Models/`
- `modules/{Module}/Routes/V1.php`

## Example Action
```php
final readonly class StoreUserAction
{
    public function __construct(
        private DatabaseManager $database
    ) {}

    public function handle(StoreUserPayload $payload): User
    {
        return $this->database->transaction(fn() => User::create([...]));
    }
}
```
