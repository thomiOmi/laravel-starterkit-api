# Coding Standards

This project follows strict coding standards to ensure maintainability, type safety, and consistency across the modular architecture.

## 1. PHP Version & Strict Typing
- **PHP 8.4+** is required.
- Every PHP file must start with `declare(strict_types=1);`.
- Use modern PHP features such as constructor property promotion, readonly classes, and attributes.

## 2. Naming Conventions
- **Classes:** `PascalCase` (e.g., `CreateUserAction`).
- **Methods:** `camelCase` (e.g., `findById`).
- **Variables:** `camelCase` (e.g., `userData`).
- **Database Tables:** `snake_case` plural (e.g., `users`).
- **File Names:** Match the class name.

## 3. Modular Architecture
Code is organized into modules located in the `modules/` directory. Each module should be self-contained.
- **Controllers:** Handle HTTP requests and return responses. Should not contain business logic.
- **Actions:** Encapsulate a single business logic task (e.g., `RegisterUserAction`).
- **Repositories:** Handle data persistence and retrieval.
- **DTOs (Data Transfer Objects):** Strictly typed objects for passing data between layers.
- **Filters:** Dedicated classes for handling search, sorting, and filtering logic.

## 4. Type Safety & Generics
- Use PHPStan Level 9 for static analysis.
- Use generics (e.g., `@template T of Model`) in Base classes like `BaseRepository` and `BaseFilter`.
- Avoid `mixed` types where possible; use specific type hints or type guards.
- Use Laravel 13 attributes for model configuration (e.g., `#[Fillable]`, `#[Hidden]`).

## 5. Error Handling
- Use `App\Traits\ApiResponser` for consistent JSON responses.
- Validation should be handled via Form Requests.
- Global exceptions are handled in `bootstrap/app.php`.

## 6. Testing
- Use **Pest 4** for testing.
- Architecture tests are used to enforce structural rules (e.g., Controllers must not import Models directly).
- Each module should have its own `Tests/` directory.

## 7. Formatting
- Use **Laravel Pint** for code formatting.
- Run `./vendor/bin/pint --format agent` before committing changes.
