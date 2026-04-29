# Laravel Starterkit API (Enterprise & SaaS Ready)

A robust and opinionated Laravel starter kit for building scalable APIs and B2B SaaS platforms. Built with Modular Architecture, Repository Pattern, and modern PHP 8.4 practices.

## 🚀 Key Features

-   **Multi-Tenancy**: Automated data and storage isolation per tenant.
-   **Subscription Management**: Plan-based feature access control.
-   **Advanced Security**: 2FA (TOTP), Password History, and mandatory rotation.
-   **Enterprise Ready**: Webhooks, API Keys, Audit Trail, and i18n support.
-   **Developer Experience**: Modular generator, Auto-API docs, and Pint formatting.

---

## 📚 Documentation

Detailed guides for using and extending the starter kit:

### 🌟 Business & SaaS Features
-   [Multi-Tenancy & Data Isolation](docs/multi-tenancy.md) - How tenant data is kept private.
-   [Subscription & Plans](docs/subscriptions.md) - Managing packages and feature access.
-   [Webhook System](docs/webhooks.md) - System-to-system automated notifications.
-   [API Key Management](docs/api-keys.md) - Secure integrations for your clients.

### 🔐 Security & Core
-   [Authentication & Device Management](docs/auth.md) - Sanctum, login flows, and devices.
-   [Security (2FA & Password Policy)](docs/security.md) - TOTP and strict password rules.
-   [RBAC (Roles & Permissions)](docs/rbac.md) - Granular access control using Spatie.
-   [Audit Trail (Logs)](docs/audit-logs.md) - Monitoring data changes and auth events.
-   [Internationalization (i18n)](docs/i18n.md) - Multi-language support (EN/ID).
-   [Health Check API](docs/health-check.md) - Monitoring system infrastructure.

### 🏗️ Technical Architecture
-   [Architecture & Data Flow](docs/architecture.md) - The Controller -> Action -> Repository pattern.
-   [Modularity & Decoupling](docs/modularity.md) - How to build and connect modules.
-   [Actions vs Services](docs/patterns.md) - When to use which pattern.
-   [API Standards](docs/api-standard.md) - Versioning, responses, and error handling.

---

## 🏗️ Technical Stack

-   **Backend**: PHP 8.4, Laravel 13, Sanctum, Spatie Permission.
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
-   **Health Check**: `/api/v1/health`
-   **Base URL**: `/api/v1/`
-   **Tenant Header**: `X-Tenant: {tenant_id}`

## 🧪 Testing

```bash
php artisan test
```

---

## 📜 License
The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
