# Modular DDD & Architecture

The project implements a Domain-Driven Modular Architecture to ensure scalability and clear separation of concerns.

## 1. Domain Modules
All business logic is grouped into modules within the `modules/` directory.

### Folder Structure (Uppercase V1)
Each module MUST follow this structure:
- `Actions/`: Single-purpose business logic classes. Must be `final readonly`.
- `Controllers/V1/`: Single-action (invokable) controllers. Must be `final readonly`.
- `Models/`: Eloquent models. Must use `strict` mode.
- `Payloads/V1/`: DTOs with Property Hooks. Must be `final`.
- `Requests/V1/`: Form requests for validation.
- `Resources/`: Eloquent API Resources.
- `Routes/V1.php`: Module-specific route definitions.

## 2. Single-Action Controllers
- **Requirement**: Use only `__invoke()`.
- **Injection**: Inject the corresponding Action via the constructor.
- **Rules**:
    - No direct Model usage.
    - No business logic.
    - Map request data to a Payload before calling the Action.

## 3. The Action Pattern
- **Responsibility**: One action per database operation.
- **Transactions**: Use `DatabaseManager $database` and `$this->database->transaction()`.
- **Composition**: Use an "Orchestrator Action" to coordinate multiple atomic actions.
- **Logging**: Actions are the primary place for domain-specific logging.

## 4. Inter-Module Communication
- **State Changes**: Use Laravel Events and Listeners for cross-module side effects.
- **Data Access**: Cross-module Model access is allowed for **read-only** operations (e.g., fetching a foreign resource).
- **Service Providers**: Keep `ModuleServiceProvider` lean. Remove empty methods.

## 5. Modern Performance
- **Defer**: Use `defer(fn() => ...)` for tasks like sending emails or webhooks that shouldn't block the response.
- **Concurrency**: Use `Concurrency::run([...])` for parallel I/O tasks.
