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
    - **Spatie Laravel Permission**: Robust Role-Based Access Control (RBAC).
- **Modern PHP & Laravel**:
    - **PHP 8.3/8.4 Support**: Leveraging the latest language features.
    - **Strict Typing**: Forced `declare(strict_types=1)` across the codebase.
    - **ULIDs**: Use of Universally Unique Lexicographically Sortable Identifiers for primary keys.
    - **Soft Deletes**: Enabled by default for data persistence safety.
- **Testing**: Integrated with **Pest PHP** for an elegant testing experience.
- **Code Quality**: Pre-configured with **Laravel Pint** for consistent coding style.

---

## Requirements

- PHP >= 8.3
- Composer
- SQLite (default) or other supported database (MySQL, PostgreSQL, etc.)

---

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
   Create a SQLite database (or configure your `.env` for other databases):
   ```bash
   touch database/database.sqlite
   ```

5. **Run Migrations & Seeders**:
   ```bash
   php artisan migrate --seed
   ```

---

## Architecture & Data Flow

This starter kit follows a strict data flow to ensure maintainability and testability:

`Controller` -> `FormRequest` -> `DTO` -> `Action` -> `Repository` -> `Model`

1.  **Controller**: Handles HTTP requests and returns standardized API responses using the `ApiResponser` trait.
2.  **FormRequest**: Handles validation and authorization for the request.
3.  **DTO**: Transfers validated data from the controller to the Action.
4.  **Action**: Contains the business logic for a specific task.
5.  **Repository**: Manages data persistence and retrieval, extending a `BaseRepository`.
6.  **Model**: Defines the database schema and relationships, utilizing the `HasDefaultBehavior` trait.

---

## Working with Modules

All business logic lives inside the `modules/` directory. Each module should have its own structure (Actions, Controllers, DTOs, Models, Providers, etc.).

### Creating a New Module

1.  Create a new directory in `modules/` (e.g., `modules/Blog`).
2.  Create a Service Provider (e.g., `modules/Blog/Providers/BlogServiceProvider.php`).
3.  The `App\Providers\ModuleServiceProvider` will automatically detect and register your module if the naming convention is followed.

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
