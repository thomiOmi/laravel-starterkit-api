# Laravel Starterkit API

A robust and opinionated Laravel starter kit for building scalable APIs using Modular Architecture, Repository Pattern, and modern PHP practices.

## Features

- **Modular Architecture**: Organized code by domain within the `modules/` directory.
- **Advanced Design Patterns**:
    - **Repository Pattern**: Abstracted data access logic.
    - **DTO (Data Transfer Objects)**: Typed data transfer between layers.
    - **Actions**: Encapsulated business logic.
- **Authentication & Authorization**:
    - **Laravel Sanctum**: Secure API token authentication.
    - **Device Management**: Supports both Multi-Device (default) and Single-Device login strategies. Configurable in `LoginAction.php`.
    - **Spatie Laravel Permission**: Robust Role-Based Access Control (RBAC).
- **Modern PHP & Laravel**:
    - **PHP 8.3/8.4 Support**: Leveraging the latest language features.
    - **Strict Typing**: Forced `declare(strict_types=1)` across the codebase.
    - **ULIDs**: Use of Universally Unique Lexicographically Sortable Identifiers for primary keys.
    - **Soft Deletes**: Enabled by default for data persistence safety.
- **Testing**: Integrated with **Pest PHP** for an elegant testing experience.
- **Code Quality**: Pre-configured with **Laravel Pint** for consistent coding style.
- **Auto API Documentation**: Integrated with **Scramble** for zero-annotation OpenAPI documentation.
- **Release Management**: Integrated with **ShipMark** for automated versioning and changelogs.
---

## Requirements

- PHP >= 8.3
- Composer
- Database (MySQL, PostgreSQL, SQLite, etc.)

---

## API Documentation

Once the application is running, you can access the automatic API documentation:

- **Swagger UI**: `/docs/api`

## Release Management

This project uses [ShipMark](https://github.com/Grazulex/shipmark) for managing releases. To create a new release:

```bash
composer run release
```

This will guide you through version bumping, changelog generation, and tagging.

## Installation

### Quick Start

Run the following command to install dependencies, set up the environment, and run migrations:

```bash
composer run setup
```

### Manual Installation

1. **Clone the repository**:

    ```bash
    git clone <repository-url>
    cd laravel-staterkit-api
    ```

2. **Install dependencies**:

    ```bash
    composer install
    ```

3. **Environment Setup**:

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4. **Database Configuration**:
   Configure your `.env` for your database (MySQL, PostgreSQL, SQLite, etc.).

5. **Run Migrations & Seeders**:
    ```bash
    php artisan migrate --seed
    ```

---

## Architecture & Data Flow

This starter kit follows a strict data flow to ensure scalability and ease of testing:

`Controller` -> `Filter` -> `Repository` -> `Model`
`Controller` -> `FormRequest` -> `DTO` -> `Service` -> `Action` -> `Repository` -> `Model`

1.  **Controller**: Handles HTTP requests, validates input, and returns standardized API responses.
2.  **Filter**: Handles data filtering logic (searching, sorting) declaratively in `modules/*/Filters`.
3.  **DTO (Data Transfer Object)**: Typed data containers for moving data between layers safely.
4.  **Service**: Orchestrator for complex business logic involving multiple actions or database transactions.
5.  **Action**: Single Responsibility units of work that can be reused across the application.
6.  **Repository**: Data access abstraction, supporting query state and filtering.
7.  **Model**: Database schema definitions using **ULID** and **Soft Deletes** by default.

---

## Modularity Standards

### 1. Module Directory Structure
Every module inside the `modules/` directory follows this standard structure:
- `Actions/`: Atomic business logic.
- `Controllers/`: API entry points.
- `DTOs/`: Data containers.
- `Events/ & Listeners/`: Cross-module communication (decoupled).
- `Filters/`: Query filtering logic (search, sort).
- `Models/`: Eloquent models.
- `Providers/`: Local module service registration.
- `Repositories/`: Database abstractions.
- `Resources/`: API output transformations.
- `Routes/`: API route definitions.

### 2. Module Generator (Highly Recommended)
Use the interactive custom artisan command to generate new modules:

```bash
php artisan make:module {ModuleName}
```

This command will prompt you for the components you want to create and automatically wire up the integration between Controller, Filter, and Repository.

---

## Cross-Module Communication (Decoupling)

Avoid calling classes across modules directly whenever possible. Use **Events & Listeners**:
- **Event**: Placed in the source module (e.g., `UserCreated` in the User module).
- **Listener**: Placed in the reacting module (e.g., `AssignDefaultRole` in the Role module).

Register these relationships in the `boot()` method of the reacting module's `ServiceProvider`.

---

## Action vs Service: When to Use?

- **Use ACTION** for single, atomic tasks (e.g., `UpdatePassword`). One class, one `execute()` method.
- **Use SERVICE** for business processes involving multiple steps, database transactions (`DB::transaction`), or coordination between multiple actions/modules.

---

---

## Middleware Best Practices

### Global Middleware
Global middleware are executed for every HTTP request. Register them in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(MyGlobalMiddleware::class);
})
```

### Route-Specific Middleware
For modular route-specific middleware, it is best practice to define them in your module's route file.

1.  **Register Alias** (Optional): In `bootstrap/app.php` if you want a shorthand.
    ```php
    $middleware->alias([
        'my-middleware' => \App\Http\Middleware\MyMiddleware::class,
    ]);
    ```

2.  **Apply to Module Routes**: In `modules/*/Routes/api.php`.
    ```php
    Route::middleware(['auth:sanctum', 'my-middleware'])->group(function () {
        Route::get('/feature', [MyController::class, 'index']);
    });
    ```

---

## Coding Standards

- **Strict Typing**: All files must include `declare(strict_types=1);`.
- **Return Types**: All methods must have explicit return type declarations.
- **Documentation**: Use PHPDoc blocks for classes and methods.
- **Formatting**: Run Laravel Pint to format your code:
    ```bash
    vendor/bin/pint
    ```

---

## Testing

Run the test suite using Pest:

```bash
php artisan test
```

Or run tests with compact output:

```bash
php artisan test --compact
```

---

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
