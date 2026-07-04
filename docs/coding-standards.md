# Coding Standards Handbook

Coding standards in this project focus on **Type Safety**, **Readability**, and leveraging the latest **PHP 8.4** features.

---

## 1. Property Hooks (PHP 8.4)

Use Property Hooks for logic tied to properties. It is cleaner than traditional getters/setters or Laravel Attributes.

### Complex Example:
```php
final class User extends Model
{
    /**
     * Hook to ensure email is always lowercase
     * and full name is calculated dynamically.
     */
    public string $email {
        set(string $value) => strtolower($value);
    }

    public string $fullName {
        get => "{$this->first_name} {$this->last_name}";
    }
}
```

---

## 2. Action-Payload Pattern

This pattern ensures your business logic can be called from anywhere (Controller, Queue, or CLI) with consistently validated data.

### Implementation:
1. **Payload:** A `final readonly` class that wraps input data.
2. **Action:** A `final readonly` class with a single `handle()` function.

```php
// Payloads/RegisterUserPayload.php
final readonly class RegisterUserPayload {
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}

// Actions/RegisterUserAction.php
final readonly class RegisterUserAction {
    public function handle(RegisterUserPayload $payload): User {
        return DB::transaction(fn() => User::create([...]));
    }
}
```

---

## 3. Strict Quality Rules

Code quality is non-negotiable. We use automated tools to enforce these standards.

### No-Ignore Policy
- **DO NOT** use `@phpstan-ignore-line` or similar annotations.
- If there is a static analysis error, it's a sign your code is not safe. **Fix the code, do not hide the error.**

### Native Type Hints
- Use type hints on **all** class properties, method parameters, and return types.
- Explicitly use `?` for nullable types.

### Final by Default
- All new classes MUST be marked as `final`.
- Use inheritance only if it is explicitly designed and strictly necessary.

---

## 4. Linting & Formatting

Run these commands before committing:
```bash
# Automatically fix formatting
./vendor/bin/pint --format agent

# Run static analysis
./vendor/bin/phpstan analyse
```
