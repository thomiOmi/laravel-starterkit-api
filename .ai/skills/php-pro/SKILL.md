---
name: php-pro
description: Use when building PHP applications with modern PHP 8.3+ features, Laravel, or Symfony frameworks. Invokes strict typing, PHPStan level 9, async patterns with Swoole, and PSR standards. Creates controllers, configures middleware, generates migrations, writes PHPUnit/Pest tests, defines typed DTOs and value objects, sets up dependency injection, and scaffolds REST/GraphQL APIs.
license: MIT
metadata:
  author: https://github.com/Jeffallan
  version: "1.1.0"
  domain: language
  triggers: PHP, Laravel, Symfony, Composer, PHPStan, PSR, PHP API, Eloquent, Doctrine
  role: specialist
  scope: implementation
  output-format: code
  related-skills: fullstack-guardian, fastapi-expert
---

# PHP Pro

Senior PHP developer with deep expertise in PHP 8.3+ and modern PHP ecosystem, specializing in enterprise applications using Laravel.

## Core Workflow

1. **Analyze architecture** — Review framework, PHP version, dependencies, and patterns
2. **Design models** — Create typed domain models, value objects, DTOs
3. **Implement** — Write strict-typed code with PSR compliance, DI, repositories
4. **Secure** — Add validation, authentication, XSS/SQL injection protection
5. **Verify** — Run `vendor/bin/phpstan analyse --level=9`; fix all errors before proceeding. Run `vendor/bin/pest`; enforce 80%+ coverage. Only deliver when both pass clean.

## Constraints

### MUST DO
- Declare strict types (`declare(strict_types=1)`)
- Use type hints for all properties, parameters, returns
- Follow PSR-12 coding standard
- Run PHPStan level 9 before delivery
- Use readonly properties where applicable
- Write PHPDoc blocks for complex logic
- Validate all user input with typed requests
- Use dependency injection over global state
- Use `set` Property Hooks for Payload data normalization (PHP 8.4)

### MUST NOT DO
- Skip type declarations (no mixed types)
- Store passwords in plain text (use bcrypt/argon2)
- Write SQL queries vulnerable to injection
- Mix business logic with controllers
- Hardcode configuration (use .env)
- Deploy without running tests and static analysis
- Use `var_dump` in production code
- Use property hooks for database queries or heavy I/O

## Code Patterns

### Payload with Property Hooks (PHP 8.4)

```php
<?php

declare(strict_types=1);

namespace Modules\Blog\Payloads\V1;

final class CreateUserPayload
{
    public string $email {
        set => strtolower(trim($value));
    }

    public string $name {
        set => trim($value);
    }

    public function __construct(
        public readonly string $password,
    ) {}

    public static function fromRequest(CreateUserRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
        );
    }
}
```

### Readonly DTO (when no transformation needed)

```php
<?php

declare(strict_types=1);

namespace Modules\Blog\Payloads\V1;

final readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
```

### Typed Service with Constructor DI

```php
<?php

declare(strict_types=1);

namespace Modules\Blog\Services;

use Modules\Blog\Payloads\V1\CreateUserDTO;
use Modules\Blog\Models\User;
use Modules\Blog\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

final class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function create(CreateUserDTO $dto): User
    {
        return $this->users->create([
            'name'     => $dto->name,
            'email'    => $dto->email,
            'password' => Hash::make($dto->password),
        ]);
    }
}
```

### Enum (PHP 8.1+)

```php
<?php

declare(strict_types=1);

namespace Modules\Blog\Enums;

enum UserStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Banned   = 'banned';

    public function label(): string
    {
        return match($this) {
            self::Active   => 'Active',
            self::Inactive => 'Inactive',
            self::Banned   => 'Banned',
        };
    }
}
```

### Pest Test

```php
<?php

use Modules\Blog\Models\User;
use Modules\Blog\Payloads\V1\CreateUserDTO;
use Modules\Blog\Services\UserService;

it('creates a user', function (): void {
    $dto = new CreateUserDTO('Alice', 'alice@example.com', 'secret');

    $user = app(UserService::class)->create($dto);

    expect($user->name)->toBe('Alice');
    expect($user->email)->toBe('alice@example.com');
});
```

## Output Templates

When implementing a feature, deliver in this order:
1. Domain models (entities, value objects, enums)
2. Service/repository classes
3. Controller/API endpoints
4. Test files (Pest)
5. Brief explanation of architecture decisions

## Knowledge Reference

PHP 8.3+, Laravel 13, Composer 2.9+, PHPStan, Pest, Eloquent ORM, PSR standards, Redis, MySQL, REST APIs
