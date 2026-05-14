# Module Generator

This project provides a custom command to accelerate the development of new modules with a standardized structure.

## Basic Usage

To create a new module, run:

```bash
php artisan make:module {ModuleName}
```

This command will create the following folder structure and boilerplate files inside `modules/{ModuleName}`:
- **Actions:** Separate classes for Create, Update, and Delete logic.
- **Controllers/V1:** Uses dependency injection for Actions and Repositories.
- **DTOs:** For type-safe data transfer between layers.
- **Repositories:** Based on Generics for standardized data access.
- **Filters:** For centralized handling of query strings (search, sort, filter).
- **Tests/Feature:** Standard CRUD test template ready to run.
- **Database:** Migrations, Factories, and Seeders.

## Interactive Mode

By default, the generator runs in **interactive mode**. You will be asked which components you want to create. If you want to overwrite an existing module, use the `--force` option:

```bash
php artisan make:module Product --force
```

## Automatic Module Registration

Newly created modules are automatically detected if the `ModuleNameServiceProvider` exists in the `Providers` folder. The system will automatically load:
1. **Migrations:** via `$this->loadMigrationsFrom()` in the Service Provider.
2. **Routes:** via the global `RouteServiceProvider` which looks for `v1.php` files in the module's `Routes` folder.
