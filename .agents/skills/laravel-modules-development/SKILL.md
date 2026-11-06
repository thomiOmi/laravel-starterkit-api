---
name: laravel-modules-development
description: "Use for any task or question involving nWidart/laravel-modules. Activate if the user mentions modules, module:make, module commands, or a modules/ directory structure."
license: MIT
metadata:
  author: nWidart
  version: "1.1"
---

# Laravel Modules Development

## Documentation

Use `search-docs` for detailed laravel-modules patterns and documentation.

## Basic Usage

### Creating a Module

```bash
php artisan module:make Blog --no-interaction
```

### Enabling / Disabling a Module

```bash
php artisan module:enable Blog --no-interaction
php artisan module:disable Blog --no-interaction
```

### Listing Modules

```bash
php artisan module:list --no-interaction
```

## Module Structure

A generated module lives under `modules/Blog/` and contains:

```text
modules/Blog/
  app/
    Http/Controllers/
    Models/
    Providers/
  config/
  database/
    migrations/
    seeders/
  resources/
    views/
  routes/
    web.php
    api.php
  tests/
  module.json
  composer.json
```

## Generating Module Components

### Controller

```bash
php artisan module:make-controller BlogController Blog --no-interaction
php artisan module:make-controller BlogController Blog --api --no-interaction
```

### Model

```bash
php artisan module:make-model Post Blog --no-interaction
php artisan module:make-model Post Blog --migration --no-interaction
php artisan module:make-model Post Blog --attributes --fillable=title,slug --no-interaction
```

### Migration

```bash
php artisan module:make-migration create_posts_table Blog --no-interaction
```

### Seeder

```bash
php artisan module:make-seed PostDatabaseSeeder Blog --no-interaction
```

### Request

```bash
php artisan module:make-request StorePostRequest Blog --no-interaction
```

### Provider

```bash
php artisan module:make-provider BlogServiceProvider Blog --no-interaction
```

### Event and Listener

```bash
php artisan module:make-event PostCreated Blog --no-interaction
php artisan module:make-listener SendPostNotification Blog --no-interaction
```

#### Event discovery

Laravel's event discovery finds listeners inside modules, but the package adds
no discovery paths. Add the module listener directories in `bootstrap/app.php`:

```php
->withEvents(discover: [
    __DIR__.'/../app/Listeners',
    __DIR__.'/../modules/*/app/Listeners',
])
```

- Check the result with `php artisan event:list`.
- In production, `php artisan event:cache` (or `optimize`) caches discovery,
  so nothing is scanned per request.
- The glob also matches disabled modules. Their listeners are still discovered
  and run if the class autoloads.
- `$shouldDiscoverEvents` in a module `EventServiceProvider` does not discover
  anything. Laravel discovers only from its base `EventServiceProvider`, so use
  `withEvents` paths.
- The package maps files under `modules.paths.modules` to module classes.
  A `DiscoverEvents::guessClassNamesUsing()` call made later, for example in
  `AppServiceProvider`, replaces this mapping.

### Job

```bash
php artisan module:make-job ProcessPost Blog --no-interaction
php artisan module:make-job-middleware RateLimited Blog --no-interaction
```

Job middleware lands in `modules/Blog/app/Jobs/Middleware` with the namespace
`Modules\Blog\Jobs\Middleware`.

### Middleware

```bash
php artisan module:make-middleware EnsureUserIsAdmin Blog --no-interaction
```

### Command

```bash
php artisan module:make-command SyncPosts Blog --no-interaction
```

### Policy

```bash
php artisan module:make-policy PostPolicy Blog --no-interaction
```

### Resource

```bash
# Generate a resource
php artisan module:make-resource PostResource Blog --no-interaction

# Generate a resource collection
php artisan module:make-resource PostCollection Blog --no-interaction
php artisan module:make-resource Posts Blog --collection --no-interaction

# Generate a JSON:API resource (Laravel 13+; --collection wins over --json-api)
php artisan module:make-resource PostResource Blog --json-api --no-interaction
```

### Config

```bash
php artisan module:make-config stripe Blog --no-interaction
```

Creates `modules/Blog/config/stripe.php`, read as `config('blog.stripe.key')`.
Nested names work: `services/stripe` becomes `config('blog.services.stripe')`.

## Running Module Migrations

```bash
php artisan module:migrate Blog --no-interaction
php artisan module:migrate-rollback Blog --no-interaction
php artisan module:migrate-refresh Blog --no-interaction
php artisan module:migrate-reset Blog --no-interaction
```

## Inertia Support

When using Inertia.js inside a module:

```bash
# Generate an Inertia-powered module
php artisan module:make Blog --inertia --no-interaction

# Generate an Inertia page inside a module
php artisan module:make-inertia-page Index Blog --no-interaction
php artisan module:make-inertia-page Index Blog --react --no-interaction
php artisan module:make-inertia-page Index Blog --vue --no-interaction
php artisan module:make-inertia-page Index Blog --svelte --no-interaction

# Generate an Inertia component inside a module
php artisan module:make-inertia-component Button Blog --no-interaction

# Publish the Inertia app.js entry point
php artisan module:publish-inertia --no-interaction
```

## Key Conventions

- Module classes are namespaced as `Modules\{ModuleName}`.
- Module routes are loaded from `modules/{Name}/routes/web.php` and `api.php`.
- Module views are referenced as `{lowercase-name}::{view}`.
- Module configs live in `modules/{Name}/config/{name}.php` and are merged at
  `{name}`.
- Module service providers are auto-discovered from `module.json` `providers`.
- Keep business logic inside the module; avoid coupling modules together and
  use events/listeners instead.
- Use `module:publish-config`, `module:publish-migration`, or
  `module:publish-translation` to publish module assets to the host app.

## Repository-specific conventions

- Follow the `modular-architecture` skill for this project's DDD layers,
  API versioning, response types, and module dependency rules.
- The project uses lowercase `modules/`, not the package documentation's
  default uppercase `Modules/` directory.
- Run `php artisan module:check-dependencies --no-interaction` after changing
  `module.json` dependency declarations.
- Do not edit files under `vendor/`; fix application configuration,
  providers, tests, or module manifests instead.
