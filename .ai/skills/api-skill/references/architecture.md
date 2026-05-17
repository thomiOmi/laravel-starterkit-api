# Modular Architecture Standard

This project follows a strict **Domain-Driven Modular Architecture**. Modules are self-contained units of business logic.

---

## 1. Module Structure

Every module must adhere to this exact structure:

```text
modules/
  {Module}/
    Actions/            # Final readonly classes with a single handle() method.
    Controllers/
      V1/               # Final single-action controllers (__invoke).
    Payloads/
      V1/               # Final readonly classes for data transfer.
    Models/             # Eloquent models (minimal logic).
    Requests/
      V1/               # Validation and Authorization logic.
    Resources/          # Eloquent resources for JSON shaping.
    Routes/
      V1.php            # Route definitions for the specific version.
    Filters/            # BaseFilter implementations for searching.
    Events/             # Domain events (Side-effects).
    Listeners/          # Event listeners.
    Exceptions/         # Module-specific domain exceptions.
    Database/
      Migrations/
      Factories/
      Seeders/
    Tests/
      Feature/
        V1.php          # V1 specific feature tests.
```

## 2. Dependency Rules

1.  **Strict Boundaries**: Modules should communicate primarily via **Events**.
2.  **Model Sharing**: Reading models from other modules is permitted for retrieval only.
3.  **No Direct Action Calls**: Avoid calling Action B from Module B directly inside Action A from Module A. Use events or an Orchestrator if they are in the same module.
4.  **No Circular Dependencies**: Module A cannot depend on Module B if Module B already depends on Module A.

## 3. Versioning Strategy

- Versioning is strictly enforced at the directory level (`V1/`, `V2/`).
- Routes are version-prefixed (`/api/V1/resource`).
- When a breaking change occurs, a new version folder must be created. Do not overwrite existing versioned logic.
