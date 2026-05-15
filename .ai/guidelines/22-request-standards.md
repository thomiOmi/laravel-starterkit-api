# Request Standards

We use Laravel's **Form Requests** to handle validation and input sanitization. This ensures that only validated data enters our application's business logic.

## 1. Validation Layer

Every request that mutates state (POST, PUT, PATCH, DELETE) must have a dedicated Form Request class.

- **Location**: `modules/{Module}/Requests/{Version}/{Action}Request.php`.
- **Inheritance**: Must extend `Illuminate\Foundation\Http\FormRequest`.
- **Documentation**: Use Scramble attributes like `#[BodyParameter]` to document the request body.

## 2. Type-Safe Input Retrieval

Inside the Form Request or Controller, always use Laravel's type-safe methods to retrieve input. Avoid raw array access where possible.

| Method | Use Case |
|---|---|
| `$request->string('key')` | Retrieves input as a `Stringable` object. |
| `$request->integer('key')` | Retrieves input as an integer. |
| `$request->boolean('key')` | Retrieves input as a boolean. |
| `$request->date('key')` | Retrieves input as a Carbon instance. |
| `$request->collect('key')` | Retrieves input as a Collection. |

### Example:
```php
public function payload(): StoreUserPayload
{
    return new StoreUserPayload(
        name:     $this->string('name')->trim()->toString(),
        age:      $this->integer('age', 18),
        isActive: $this->boolean('is_active'),
    );
}
```

## 3. Transition to Payloads

As detailed in [03-payloads.md](03-payloads.md), the Form Request is responsible for transforming the validated input into a **Payload** object. This decouples our business logic (Actions) from the HTTP request layer.

## 4. Anti-Patterns

- ❌ Do not use `$request->all()` or `$request->input()` without validation.
- ❌ Do not perform validation directly inside the Controller.
- ❌ Do not use manual type casting (e.g., `(int) $request->id`) if `$request->integer()` can be used.
- ❌ Do not pass the `Request` object into an Action or Service.
- ❌ Do not use `nullable` validation without an appropriate default value in the Payload.
