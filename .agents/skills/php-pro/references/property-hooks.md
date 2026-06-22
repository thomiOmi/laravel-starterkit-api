# PHP 8.4 Property Hooks

Property hooks allow you to intercept and handle property access and modification directly within the class definition.

## Use Case: Data Payloads

In our Standard 2026 architecture, Property Hooks are mandatory for Payloads to ensure data integrity without external DTO builders.

### Setters (Validation & Transformation)

```php
public string $email {
    set => strtolower(trim($value));
}
```

### Getters (Derived Data)

```php
public string $fullName {
    get => $this->firstName . ' ' . $this->lastName;
}
```

## Rules

1. Always use `set` for normalization (lowercase, trim).
2. Use `set` for simple validation (throwing `InvalidArgumentException`).
3. If logic is complex, consider a dedicated private method called by the hook.
