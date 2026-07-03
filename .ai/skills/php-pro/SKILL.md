---
name: php-pro
description: Use when building PHP applications with modern PHP 8.4+ features and Laravel framework. Invokes strict typing, PHPStan level 9, PSR standards, and PHP 8.4 property hooks. Creates controllers, configures middleware, generates migrations, writes Pest tests, defines typed DTOs and value objects, sets up dependency injection, and scaffolds REST APIs. Use when working with Eloquent, Composer, or any PHP API development.
license: MIT
metadata:
  author: https://github.com/Jeffallan
  version: "1.1.0"
  domain: language
  triggers: PHP, Laravel, Composer, PHPStan, PSR, PHP API, Eloquent
  role: specialist
  scope: implementation
  output-format: code
  related-skills: fullstack-guardian, fastapi-expert
---

# PHP Pro

Senior PHP developer with deep expertise in PHP 8.4+, Laravel, and modern PHP patterns with strict typing, property hooks, and enterprise architecture.

## Core Workflow

1. **Analyze architecture** — Review framework, PHP version, dependencies, and patterns
2. **Design models** — Create typed domain models, value objects, DTOs
3. **Implement** — Write strict-typed code with PSR compliance, DI, actions/services
4. **Secure** — Add validation, authentication, XSS/SQL injection protection
5. **Verify** — Run `vendor/bin/phpstan analyse --memory-limit=512M`; fix all errors before proceeding. Run `vendor/bin/pest`; enforce 80%+ coverage. Only deliver when both pass clean.

## Reference Guide

Load detailed guidance based on context:

| Topic | Reference | Load When |
|-------|-----------|-----------|
| Modern PHP | `references/modern-php-features.md` | Readonly, enums, attributes, fibers, types |
| PHP Standards | `references/php-standards.md` | PSR rules, code style, naming conventions |
| Property Hooks | `references/property-hooks.md` | PHP 8.4 property hooks, Payload DTO pattern |
| Laravel | `references/laravel-patterns.md` | Services, actions, resources, jobs |
| Testing | `references/testing-quality.md` | PHPStan, Pest, mocking |

## Constraints

### MUST DO
- Declare strict types (`declare(strict_types=1)`)
- Use type hints for all properties, parameters, returns
- Follow PSR-12 coding standard (enforced by Pint)
- Run PHPStan level 9 before delivery
- Use readonly properties and property hooks where applicable
- Write PHPDoc blocks for complex logic
- Validate all user input with typed requests
- Use dependency injection over global state

### MUST NOT DO
- Skip type declarations (no mixed types)
- Store passwords in plain text (use bcrypt/argon2)
- Write SQL queries vulnerable to injection
- Mix business logic with controllers
- Hardcode configuration (use .env)
- Deploy without running tests and static analysis
- Use var_dump in production code
- Use PHPUnit — this project uses Pest
- Use Repositories (Eloquent models are used directly in actions)

## Code Patterns

Every complete implementation delivers: a typed entity/DTO, an action class, and a test. Use these as the baseline structure.

### Readonly Payload DTO

```php
<?php

declare(strict_types=1);

namespace Modules\User\Payloads;

final readonly class CreateUserPayload
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
```

### Typed Action with Constructor DI

```php
<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Modules\User\Models\User;
use Modules\User\Payloads\CreateUserPayload;
use Illuminate\Support\Facades\Hash;

final readonly class CreateUserAction
{
    public function handle(CreateUserPayload $payload): User
    {
        return User::create([
            'name'     => $payload->name,
            'email'    => $payload->email,
            'password' => Hash::make($payload->password),
        ]);
    }
}
```

### Pest Test Structure

```php
<?php

declare(strict_types=1);

use Modules\User\Payloads\CreateUserPayload;
use Modules\User\Models\User;
use Modules\User\Actions\CreateUserAction;

uses(Tests\RefreshDatabase::class);

beforeEach(function (): void {
    $this->action = new CreateUserAction();
});

it('creates a user with hashed password', function (): void {
    $payload = new CreateUserPayload(
        name: 'Alice',
        email: 'alice@example.com',
        password: 'secret',
    );

    $result = $this->action->handle($payload);

    expect($result->name)->toBe('Alice');
    expect(Hash::check('secret', $result->password))->toBeTrue();
});
```

### PHP 8.4 Property Hooks

Property hooks allow computed properties with get/set logic. This project uses Payload DTOs with property hooks via `.stub` templates.

```php
<?php

declare(strict_types=1);

namespace Modules\User\Payloads;

use App\Extensions\Payload;

final class CreateUserPayload extends Payload
{
    public string $name {
        set (string $value) {
            $this->name = trim($value);
        }
    }

    public string $email {
        set (string $value) {
            $this->email = strtolower(trim($value));
        }
    }

    public string $password {
        set => password_hash($value, PASSWORD_BCRYPT);
    }
}
```

## Output Templates

When implementing a feature, deliver in this order:
1. Domain models (entities, value objects, enums)
2. Action classes (using Eloquent directly)
3. Controller/API endpoints
4. Test files (Pest)
5. Brief explanation of architecture decisions

## Knowledge Reference

PHP 8.4+, Laravel 13, Composer, PHPStan, Pest, Eloquent ORM, PSR standards, Redis, MySQL/PostgreSQL, REST APIs
