# Modular Architecture Standards

This project follows a strict **Domain-Driven Modular Architecture**. Every feature or core domain must reside within its own module.

## 1. Directory Structure

Each module must follow this standardized directory layout:

```text
modules/
  {Module}/
    Actions/         # Business logic classes
    Controllers/     # V1, V2 single-action controllers
    Payloads/        # Data Transfer Objects (renamed from DTOs)
    Models/          # Eloquent models
    Requests/        # Form requests
    Resources/       # Eloquent resources
    Routes/          # v1.php, v2.php
    Filters/         # Query filters (extending BaseFilter)
    Database/        # Migrations, Factories, Seeders
    Tests/           # Feature and unit tests
    Providers/       # Module-specific service providers
```

## 2. Mandatory Components
- **Routes**: Must be versioned.
- **Controllers**: Must be single-action and invokable.
- **Models**: Must use modern PHP attributes (Fillable, Hidden).
- **Resources**: Must transform the model for the API.

## 3. Optional Components (Standardized)
- **Actions**: Highly recommended for non-trivial logic.
- **Payloads**: Recommended for state-mutating requests.
- **Filters**: Recommended for list endpoints with search/sort requirements.
- **Repositories**: Discouraged unless there is a specific need for complex data abstraction.

## 4. Module Interaction
- **Isolation**: Modules should be as decoupled as possible.
- **Cross-Module Calls**: Use Actions or Services from other modules instead of reaching into another module's Model or Repository directly if complex logic is involved.

## 5. Anti-Patterns
- ❌ Do not place domain logic in the root `app/` directory if it belongs to a module.
- ❌ Do not use a generic "ModulesController" for multiple domains.
- ❌ Do not create circular dependencies between modules.
- ❌ Do not omit the `v1` versioning in routes and controllers.
