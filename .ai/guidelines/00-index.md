# AI Guidelines Index

Welcome to the AI coding standards for this project. These guidelines are designed to help AI agents generate code that adheres to our architectural principles.

## 📋 Standards Table of Contents

### 1. Core Architecture
- [01-routing.md](01-routing.md): Modular routing, versioning, and throttling standards.
- [02-controllers.md](02-controllers.md): Mandating Single-Action (Invokable) Controllers.
- [04-logic-and-actions.md](04-logic-and-actions.md): Business logic standards using Actions and Eloquent.
- [15-dependency-injection.md](15-dependency-injection.md): Constructor injection standards.

### 2. Data & Communication
- [03-payloads.md](03-payloads.md): Data transfer standards using Payloads (renamed from DTOs).
- [05-responses-and-errors.md](05-responses-and-errors.md): API success and RFC 9457 error standards.
- [16-http-status-codes.md](16-http-status-codes.md): Standardization using Symfony Response constants.
- [09-background-jobs.md](09-background-jobs.md): Non-blocking processing and queue standards.
- [13-pagination.md](13-pagination.md): Simple pagination standards to avoid database overhead.

### 3. Security, Middleware & Models
- [06-auth-and-permissions.md](06-auth-and-permissions.md): Authentication (Sanctum) and RBAC (Spatie Permission) standards.
- [07-model-standards.md](07-model-standards.md): Flexible identifiers and Eloquent strictness.
- [12-middleware-and-cors.md](12-middleware-and-cors.md): Force JSON, Sunset middleware, and CORS configuration.

### 4. Development Standards & Examples
- [08-code-quality.md](08-code-quality.md): PHP 8.4+ standards, strict typing, and class design.
- [10-testing-standards.md](10-testing-standards.md): Pest PHP and outside-in testing principles.
- [11-naming-conventions.md](11-naming-conventions.md): Comprehensive naming guide for all layers.
- [14-worked-examples.md](14-worked-examples.md): Complete, copy-pasteable implementation examples.

---

## 🚀 How to use these guidelines

If you are an AI agent:
1. **Read these guidelines upfront** before suggesting or writing any code.
2. **Follow the prescriptive rules** (e.g., use `final` classes, `__invoke` controllers, and `Payloads`).
3. **Avoid the prohibited patterns** listed in the "Anti-Patterns" section of each file.
4. **Prefer Actions over Repositories** for all business logic.
