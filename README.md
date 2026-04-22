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
- **Auto API Documentation**: Integrated with **Scramble** for zero-annotation OpenAPI documentation.
- **Release Management**: Integrated with **ShipMark** for automated versioning and changelogs.
- **Manual API Versioning**: Lightweight, folder-based API versioning support.

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

## API Versioning

API versioning is managed manually via `config/apiroute.php`. Routes are located in `modules/*/Routes/{version}.php`.

### Adding a New Version
1. Create a new route file (e.g., `modules/Blog/Routes/v2.php`).
2. Define your controllers in a corresponding namespace (e.g., `Modules\Blog\Controllers\V2`).
3. Register the new version in `config/apiroute.php`.

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

## Extending Modules (Adding New Fields)

To add new fields to an existing module, follow these steps:

### 1. Create a New Migration

```bash
php artisan make:migration add_avatar_to_users_table --table=users
```

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('avatar')->nullable()->after('email');
    });
}
```

### 2. Update the Model

Add the new field to the `#[Fillable]` attribute.

```php
#[Fillable(['name', 'email', 'password', 'avatar'])]
class User extends Authenticatable { ... }
```

### 3. Update the DTO

Add the property and update the `fromRequest` method.

```php
public function __construct(
    public string $name,
    public string $email,
    public ?string $password = null,
    public ?string $avatar = null
) {}

public static function fromRequest($request): self
{
    return new self(
        name: $request->validated('name'),
        email: $request->validated('email'),
        password: $request->validated('password'),
        avatar: $request->validated('avatar')
    );
}
```

### 4. Update the Form Request

Add validation rules for the new field.

```php
'avatar' => ['nullable', 'string', 'max:255'],
```

### 5. Update the API Resource

Include the new field in the response.

```php
'avatar' => $this->avatar,
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
