# Laravel Starterkit API (Enterprise Ready)

A robust and opinionated Laravel starter kit for building scalable APIs. Built with Modular Architecture, Repository Pattern, and modern PHP 8.4 practices.

## 🚀 Key Features

-   **Feature Flags**: Dynamic feature control using Laravel Pennant.
-   **Enterprise Ready**: Clean Modular Architecture, Strict Typing (Level 9), and i18n support.
-   **Developer Experience**: Advanced Modular generator, Auto-API docs, and Pint formatting.

---

## 📚 Documentation

Detailed guides for using and extending the starter kit:

### 🌟 Business Features
-   [Feature Flags](docs/feature-flags.md) - Managing dynamic feature access with Pennant.

### 🔐 Security & Core
-   [Authentication & Device Management](docs/auth.md) - Sanctum, login flows, and devices.
-   [RBAC (Roles & Permissions)](docs/rbac.md) - Granular access control using Spatie.

### 🏗️ Technical Architecture
-   [Architecture & Data Flow](docs/architecture.md) - The Controller -> Action -> Repository pattern.
-   [Events & Queues](docs/events.md) - Background processing and decoupled logic.
-   [API Standards](docs/api-standard.md) - Versioning, responses, and error handling.
-   [Module Generator](docs/module-generator.md) - Rapid development with standard boilerplate.
-   [Coding Standards](docs/coding-standards.md) - Project-wide standards and best practices.

---

## 🏗️ Technical Stack

-   **Backend**: PHP 8.4, Laravel 13, Sanctum, Spatie Permission, Laravel Pennant.
-   **Testing**: Pest PHP.
-   **Documentation**: Scramble (OpenAPI/Swagger).
-   **Standards**: Modular Architecture, DTOs, Repository Pattern.

---

## 🛠️ Installation

### Quick Start
```bash
composer run setup
```

### Manual Installation
1.  **Clone & Install**: `composer install`
2.  **Environment**: `cp .env.example .env && php artisan key:generate`
3.  **Database**: Configure `.env` then run `php artisan migrate --seed`

---

## 🚀 Quick Access

-   **Swagger UI**: `/docs/api`
-   **Base URL**: `/api/v1/`

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run tests for a specific module
./vendor/bin/pest modules/User
```

---

## 📜 License
The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
