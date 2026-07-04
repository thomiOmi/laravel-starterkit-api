# Laravel Starterkit API (Industrial-Grade)

> A high-performance, modular monolith boilerplate built with Laravel 13 and PHP 8.4, designed for scalability and enterprise-level maintainability.

---

## 🎯 Motivation

This starterkit was built with one primary goal: **To eliminate the repetition of setting up real-world projects.**

Most starterkits are either too generic or overly complex. This project takes a middle ground, providing a rock-solid yet flexible foundation for professional applications.

### Why does this project exist?
1. **Clone & Go Foundation:** I wanted a starterkit where I have Sanctum Auth, Spatie RBAC, and a mature API Standard ready to use. No more repeating the same basic setup for every new project.
2. **Optional Modularity:** Using a modular concept allows real-world projects to add or install new features as separate modules. Modularity is optional; if you don't need a feature, don't enable the module. If you do, it integrates perfectly with the core.
3. **Decoupled Frontend:** The main focus of this application is to serve as a backend for **SPA Frontends (Vue/React/Next.js)** or **Mobile Apps (Flutter/React Native)**. This is why API Standards (RFC 9457, Idempotency) are heavily emphasized here.

## 🚀 Tech Stack

- **Framework:** Laravel 13 (Modular Monolith)
- **Language:** PHP 8.4 (Strict Typing, Property Hooks)
- **Auth:** Laravel Sanctum (Per-device token management)
- **Permissions:** Spatie Laravel Permission (RBAC)
- **Feature Flags:** Laravel Pennant
- **Testing:** Pest PHP
- **Quality:** PHPStan (Max Level) & Laravel Pint

## 🏗️ Architecture

This project follows a **Contract-First Modular Monolith** approach.

```text
modules/
├── {Module}/
│   ├── Actions/         # Single-purpose Business Logic
│   ├── Controllers/     # Thin HTTP Layer (V1/, V2/ versioning)
│   ├── Database/        # factories/, migrations/, seeders/
│   ├── Events/          # Cross-module communication events
│   ├── Filters/         # Query/filter objects
│   ├── Jobs/            # Queued tasks
│   ├── Models/          # Eloquent Models (PHP 8.4 Property Hooks)
│   ├── Payloads/        # Type-safe DTOs (Action-Payload pattern)
│   ├── Providers/       # Module service providers
│   ├── Repositories/    # Optional: Complex data access
│   ├── Requests/        # Form request validation
│   ├── Resources/       # API Resources (JSON transformation)
│   ├── Routes/          # V1.php, V2.php
│   └── Tests/           # Feature & Unit tests
app/
├── Contracts/           # The Glue: Interfaces for module communication
├── Http/
│   ├── Controllers/     # Base Controller
│   ├── Middleware/      # Core middleware
│   └── Responses/       # Industrial Responses (RFC 9457)
└── Providers/           # AppServiceProvider, AuthServiceProvider
database/
├── factories/           # Shared factories
├── migrations/          # Shared migrations
└── seeders/             # Core seeders
```

## 💎 Core Values

1. **Zero-Cross Model Import:** Modules never import each other's Models directly.
2. **Action-Payload Pattern:** Consistent business logic regardless of transport layer (HTTP, Job, CLI).
3. **Property Hooks Standard:** Native PHP 8.4 features for more expressive and faster code.
4. **No-Ignore Quality:** Code quality is priority. Fix errors, don't ignore them.

## 🛠️ Quick Start

```bash
# 1. Clone & Install
composer install

# 2. Setup Env
cp .env.example .env && php artisan key:generate

# 3. Migrate & Seed
php artisan migrate --seed

# 4. Verification
php artisan test
```

## 📖 Documentation

- [Architecture & Modularization](docs/architecture.md)
- [API Standards (RFC 9457, Idempotency)](docs/api-standard.md)
- [Coding Standards (Property Hooks, Final Classes)](docs/coding-standards.md)
- [Authentication & RBAC](docs/auth.md)

---
Built with ❤️ for real-world projects.
