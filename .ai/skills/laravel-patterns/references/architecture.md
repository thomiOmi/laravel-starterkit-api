# Modular DDD Architecture

The project follows a domain-driven approach where each business module is isolated.

## Folder structure
`modules/{Module}/`
- `Controllers/V1/`: Single-Action Controllers.
- `Actions/`: Atomic database operations.
- `Payloads/V1/`: Data Transfer Objects.
- `Models/`: Eloquent models (strict mode).
- `Routes/V1.php`: Module routes.

## Action Pattern
Actions should handle a single responsibility and be wrapped in a database transaction.
Use the orchestrator pattern for complex cross-action logic.
