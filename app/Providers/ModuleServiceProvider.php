<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Abstract base service provider for module providers.
 *
 * Provides the loading boilerplate shared by every module: config merge,
 * build-time feature merge, migrations, routes, translations, and commands.
 * Module providers are declaration-only: they implement moduleName() and may
 * hook bootModule(); the loading order is fixed and cannot be reordered.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * The TitleCase module folder name (e.g. "Media" for modules/Media).
     */
    abstract protected function moduleName(): string;

    /**
     * Register any application services.
     */
    final public function register(): void
    {
        if (! $this->isModuleActive()) {
            return;
        }

        $this->mergeModuleConfig();

        $this->mergeModuleFeatures();
    }

    /**
     * Bootstrap any application services.
     */
    final public function boot(): void
    {
        if (! $this->isModuleActive()) {
            return;
        }

        $this->loadModuleMigrations();

        $this->loadModuleRoutes();

        $this->loadModuleTranslations();

        $this->registerModuleCommands();

        $this->bootModule();
    }

    /**
     * Hook for module-specific bootstrapping (middleware aliases, bindings).
     */
    protected function bootModule(): void
    {
        //
    }

    /**
     * The lowercase module alias derived from the module name.
     *
     * normalize: true lowercases fully-uppercased words first, so
     * "IAM" becomes "iam" (not "i_a_m") and "Media" stays "media".
     */
    protected function moduleAlias(): string
    {
        return Str::snake(Str::studly($this->moduleName(), normalize: true));
    }

    /**
     * Whether the module is active in the central registry.
     *
     * Guards register()/boot() so an inactive module stays fully inert even if
     * its provider is registered directly (e.g. in tests), not just when it is
     * skipped by ModuleLoaderServiceProvider.
     */
    protected function isModuleActive(): bool
    {
        return config()->boolean("modules.modules.{$this->moduleAlias()}.active", false);
    }

    /**
     * Merge the module config file into the config repository.
     */
    protected function mergeModuleConfig(): void
    {
        $configPath = $this->modulePath("Config/{$this->moduleAlias()}.php");

        if (File::exists($configPath)) {
            $this->mergeConfigFrom($configPath, $this->moduleAlias());
        }
    }

    /**
     * Merge the registry build-time features into config('{alias}.features').
     */
    protected function mergeModuleFeatures(): void
    {
        $features = array_merge(
            config()->array("{$this->moduleAlias()}.features", []),
            config()->array("modules.modules.{$this->moduleAlias()}.features", [])
        );

        config()->set("{$this->moduleAlias()}.features", $features);
    }

    /**
     * Load the module migrations while the module is active.
     */
    protected function loadModuleMigrations(): void
    {
        $migrationsPath = $this->modulePath('Database/Migrations');

        if (File::isDirectory($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    /**
     * Load the versioned module route files with the api/v1/{alias} prefix.
     */
    protected function loadModuleRoutes(): void
    {
        /** @var array<int, string> $versions */
        $versions = config()->array('apiroute.supported_versions', ['V1']);

        foreach ($versions as $version) {
            $routeFile = $this->modulePath("Routes/{$version}.php");

            if (! File::exists($routeFile)) {
                continue;
            }

            Route::prefix('api/'.strtolower($version))
                ->middleware(['api'])
                ->name(strtolower($version).'.'.$this->moduleAlias().'.')
                ->group($routeFile);
        }
    }

    /**
     * Load the module translations under the module alias namespace.
     */
    protected function loadModuleTranslations(): void
    {
        $langPath = $this->modulePath('Lang');

        if (File::isDirectory($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleAlias());
        }
    }

    /**
     * Register command classes discovered under the module Console directory.
     */
    protected function registerModuleCommands(): void
    {
        $commandsPath = $this->modulePath('Console/Commands');

        if (! File::isDirectory($commandsPath)) {
            return;
        }

        $namespace = "Modules\\{$this->moduleName()}\\Console\\Commands";

        $commands = [];

        foreach (File::files($commandsPath) as $file) {
            $className = "{$namespace}\\{$file->getBasename('.php')}";

            if (class_exists($className)) {
                $commands[] = $className;
            }
        }

        if ($commands !== []) {
            $this->commands($commands);
        }
    }

    /**
     * Resolve a path relative to the module directory.
     */
    protected function modulePath(string $path = ''): string
    {
        return base_path("modules/{$this->moduleName()}".($path !== '' ? "/{$path}" : ''));
    }
}
