# Coding Standards Handbook (PHP 8.4 & Laravel 13)

We embrace the latest PHP features to write cleaner, safer code.

## 1. Property Hooks (PHP 8.4)

Stop writing verbose `Attribute` classes. Use native Property Hooks.

### Before (L12 and below):
```php
protected function fullName(): Attribute
{
    return Attribute::make(
        get: fn () => "{$this->first_name} {$this->last_name}",
    );
}
```

### After (PHP 8.4):
```php
public string $fullName {
    get => "{$this->first_name} {$this->last_name}";
}
```

## 2. Final Classes

By default, every class is `final`. This prevents "Magic Inheritance" and makes the code easier to reason about.

```php
final readonly class RegisterUserAction
{
    // ...
}
```

## 3. Action-Payload Pattern

An Action should not depend on a `Request`. It should receive a **Payload**.

### The Story: Creating a User
1. **Controller** parses the `Request` into a `RegisterUserPayload`.
2. **Action** receives the `RegisterUserPayload`.
3. **Benefit:** You can now call the Action from a CLI Command or a Job using the same Payload object.

## 4. Strict Typing

Every property, parameter, and return value must have a native type hint.
```php
public function handle(UserPayload $payload): User
```

## 5. Formatting

We use **Laravel Pint**. Before every commit, run:
```bash
./vendor/bin/pint --format agent
```
