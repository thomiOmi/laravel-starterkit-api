# Laravel Starterkit API (Industrial-Grade)

> A high-performance, modular monolith boilerplate built with Laravel 13 and PHP 8.4, designed for scalability and enterprise-level maintainability.

---

## 🎯 Motivation

Modern application development often falls into two traps: **Big Ball of Mud** (Monoliths that become unmanageable) or **Microservices Overkill** (adding distributed system complexity too early).

This starter kit provides a **third way**: The **Modular Monolith**. It enforces strict module boundaries while keeping the deployment simple. By using industry standards like **RFC 9457**, **Idempotency**, and **Property Hooks**, we ensure your codebase is ready for both high traffic and high developer velocity.

## 🚀 Tech Stack

- **Framework:** Laravel 13
- **Language:** PHP 8.4 (Strict Typing, Property Hooks)
- **Auth:** Laravel Sanctum (Per-device token management)
- **Permissions:** Spatie Laravel Permission
- **Feature Flags:** Laravel Pennant
- **Testing:** Pest PHP
- **Static Analysis:** PHPStan (Max Level) & Laravel Pint

## 🏗️ Architecture Architecture

We follow a **Contract-First Modular Monolith** approach:

```
Modules/
├── IAM/                # Identity and Access Management
│   ├── Actions/        # Single-purpose business logic
│   ├── Controllers/    # Thin HTTP wrappers
│   ├── Models/         # Eloquent Models (Property Hooks)
│   ├── Payloads/       # Type-safe DTOs
│   └── Routes/         # Modular routing
app/
├── Contracts/          # The only way modules talk to each other
└── Http/Responses/     # RFC 9457 Problem Details
```

## 💎 Core Values

1. **Zero-Cross Model Import:** Module A never imports Module B's models.
2. **Action-Payload Pattern:** Consistent business logic regardless of HTTP or Job context.
3. **Industry Standards:** Native support for Idempotency, Rate Limiting, and Problem Details.
4. **Developer Experience (DX):** Fully integrated with **Laravel Boost** and **Agent Skills** for AI-assisted development.

## 🛠️ Quick Start

1. **Install Dependencies:**
   ```bash
   composer install
   ```

2. **Setup Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database & Auth:**
   ```bash
   php artisan migrate --seed
   ```

4. **Run Tests:**
   ```bash
   php artisan test
   ```

## 📖 Documentation

Detailed handbooks are available in the `docs/` directory:
- [Architecture & Modularization](docs/architecture.md)
- [API Standards (RFC 9457, Idempotency)](docs/api-standard.md)
- [Coding Standards (Property Hooks, Final Classes)](docs/coding-standards.md)
- [Authentication & RBAC](docs/auth.md)

---
Built with ❤️ for the Laravel Community.
