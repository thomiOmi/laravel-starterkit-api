# AI Guidelines Index (Skill References)

Welcome to the AI coding standards for this project. These references are part of the `api-skill` and help AI agents generate code that adheres to our architectural principles.

## 📋 Standards Table of Contents

### 1. Core Architecture
- [01-routing.md](01-routing.md): Modular routing, versioning, and throttling standards.
- [02-controllers.md](02-controllers.md): Mandating Single-Action (Invokable) Controllers.
- [04-logic-and-actions.md](04-logic-and-actions.md): Business logic standards using Actions and Eloquent.
- [15-dependency-injection.md](15-dependency-injection.md): Constructor injection standards.
- [21-modular-architecture.md](21-modular-architecture.md): Domain-driven modular directory structure.

### 2. Data & Communication
- [03-payloads.md](03-payloads.md): Data transfer standards using Payloads (renamed from DTOs).
- [22-request-standards.md](22-request-standards.md): Validation and type-safe input retrieval best practices.
- [23-success-responses.md](23-success-responses.md): Standardized JSON success and paginated response structures.
- [05-error-handling.md](05-error-handling.md): RFC 9457 error standards and exception mapping.
- [16-http-status-codes.md](16-http-status-codes.md): Standardization using Symfony Response constants.
- [09-background-jobs.md](09-background-jobs.md): Non-blocking processing and queue standards.
- [13-pagination.md](13-pagination.md): Simple pagination standards to avoid database overhead.
- [19-query-filtering.md](19-query-filtering.md): Standardized filtering and sorting using `BaseFilter`.

### 3. Security, Middleware & Documentation
- [06-auth-and-permissions.md](06-auth-and-permissions.md): Authentication (Sanctum) and RBAC (Spatie Permission) standards.
- [07-model-standards.md](07-model-standards.md): Flexible identifiers and Eloquent strictness.
- [12-middleware.md](12-middleware.md): Force JSON and Sunset (Deprecation) middleware.
- [24-cors.md](24-cors.md): Cross-Origin Resource Sharing configuration.
- [18-rate-limiting.md](18-rate-limiting.md): Mandatory throttling for all API endpoints.
- [20-api-documentation.md](20-api-documentation.md): Auto-generated documentation using Scramble attributes.

### 4. Development Standards, Examples & References
- [08-code-quality.md](08-code-quality.md): PHP 8.4+ standards, strict typing, and class design.
- [10-testing-standards.md](10-testing-standards.md): Pest PHP and outside-in testing principles.
- [11-naming-conventions.md](11-naming-conventions.md): Comprehensive naming guide for all layers.
- [14-worked-examples.md](14-worked-examples.md): Complete, copy-pasteable implementation examples.
- [17-anti-patterns.md](17-anti-patterns.md): Prohibited patterns to avoid.
- [99-references.md](99-references.md): External documentation and RFC links.

---

## 🚀 How to use these references

If you are an AI agent:
1. **Read these files upfront** before suggesting or writing any code.
2. **Follow the prescriptive rules** (e.g., use `final` classes, `__invoke` controllers, and `Payloads`).
3. **Avoid the prohibited patterns** listed in the [Anti-Patterns](17-anti-patterns.md) section.
4. **Prefer Actions over Repositories** for all business logic.
