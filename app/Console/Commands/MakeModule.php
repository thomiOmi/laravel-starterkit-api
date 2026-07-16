<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Signature('make:module {name? : The name of the module} {--force : Overwrite existing files} {--api-version=V1 : API version} {--x|except= : Comma-separated components to skip (repository,action,filter,migration,factory,seeder,event)} {--E|event : Create event} {--r|repository : Create concrete repository} {--a|action : Create CRUD actions & payloads} {--l|filter : Create query filter} {--m|migration : Create migration} {--y|factory : Create factory} {--s|seeder : Create seeder}')]
#[Description('Create a new module with controllers, model, resource, tests, and optional components. Supports shorthand flags (-Eralmys) and --except to skip components.')]
class MakeModule extends Command
{
    public function handle(): void
    {
        $nameArgument = $this->argument('name');
        $name = is_string($nameArgument) ? $nameArgument : '';

        if ($name === '') {
            $askedName = $this->ask('What is the name of the module? (e.g. Blog)');
            $name = is_string($askedName) ? $askedName : '';
        }

        if ($name === '') {
            $this->error('Module name is required!');

            return;
        }

        $name = Str::studly($name);
        $version = strtoupper((string) $this->option('api-version'));
        $basePath = base_path('modules');
        $modulePath = "{$basePath}/{$name}";

        if (File::exists($modulePath) && ! $this->option('force')) {
            if (! $this->confirm("Module {$name} already exists. Do you want to overwrite it?", false)) {
                $this->info('Aborted.');

                return;
            }
        }

        $options = [
            'repository' => $this->resolveOption('repository', 'Create Concrete Repository?'),
            'action' => $this->resolveOption('action', 'Create CRUD Actions & Payloads?'),
            'filter' => $this->resolveOption('filter', 'Create Query Filter?'),
            'migration' => $this->resolveOption('migration', 'Create Migration?'),
            'factory' => $this->resolveOption('factory', 'Create Factory?'),
            'seeder' => $this->resolveOption('seeder', 'Create Seeder?'),
            'event' => $this->resolveOption('event', 'Create Event?'),
        ];

        $this->info("Generating module {$name} ({$version})...");

        $this->createDirectories($modulePath, $version, $options);
        $this->createFiles($name, $modulePath, $version, $options);

        $this->info("Module {$name} created successfully!");
        $this->showSummary($name, $version, $options);
    }

    /**
     * Resolve a boolean option: check --except list first, then --{name} flag,
     * then interactive confirm, then default.
     */
    protected function resolveOption(string $name, string $question, bool $default = true): bool
    {
        $except = (string) $this->option('except');
        if ($except !== '' && in_array($name, array_map('trim', explode(',', $except)), true)) {
            return false;
        }

        if ($this->option($name) === true) {
            return true;
        }

        return $this->confirm($question, $default);
    }

    /**
     * @param  array<string, bool>  $options
     */
    protected function createDirectories(string $path, string $version, array $options): void
    {
        $directories = [
            "Controllers/{$version}",
            'Models',
            'Providers',
            'Resources',
            'Routes',
            "Tests/Feature/{$version}",
        ];

        if ($options['repository']) {
            $directories[] = 'Repositories';
        }

        if ($options['action']) {
            $directories[] = 'Actions';
            $directories[] = "Payloads/{$version}";
            $directories[] = "Requests/{$version}";
        }
        if ($options['filter']) {
            $directories[] = 'Filters';
        }

        if ($options['event']) {
            $directories[] = 'Events';
        }

        if ($options['migration'] || $options['factory'] || $options['seeder']) {
            $directories[] = 'Database/Migrations';
            if ($options['factory']) {
                $directories[] = 'Database/Factories';
            }
            if ($options['seeder']) {
                $directories[] = 'Database/Seeders';
            }
        }

        foreach ($directories as $dir) {
            File::makeDirectory("{$path}/{$dir}", 0755, true, true);
        }
    }

    /**
     * @param  array<string, bool>  $options
     */
    protected function createFiles(string $name, string $path, string $version, array $options): void
    {
        $pluralName = Str::plural($name);

        $replacements = [
            'Module' => $name,
            'pluralModule' => $pluralName,
            'Version' => $version,
            'lowerResource' => Str::camel($name),
            'label' => Str::lower(Str::headline($name)),
            'labelPlural' => Str::lower(Str::headline(Str::plural($name))),
            'slug' => Str::kebab(Str::plural($name)),
            'tableName' => Str::snake(Str::plural($name)),
        ];

        $this->createFileFromStub($path."/Providers/{$name}ServiceProvider.php", 'provider', $replacements);

        $this->createFileFromStub($path."/Routes/{$version}.php", 'route', array_merge($replacements, [
            'routesContent' => $this->getRoutesContent($name, $version, $options),
        ]));

        $this->createFileFromStub($path."/Models/{$name}.php", 'model', $replacements);

        // Repository
        if ($options['repository']) {
            $this->createFileFromStub($path."/Repositories/{$name}Repository.php", 'repository', $replacements);
        }

        // Controllers
        $this->createFileFromStub($path."/Controllers/{$version}/ListController.php", 'controller.index', $replacements);

        $this->createFileFromStub($path."/Resources/{$name}Resource.php", 'resource', $replacements);

        if ($options['action']) {
            // List Action
            $this->createFileFromStub($path."/Actions/List{$pluralName}Action.php", 'action.index', $replacements);

            foreach (['Create', 'Update'] as $action) {
                $actionLower = strtolower($action);
                $actionReplacements = array_merge($replacements, ['Action' => $action]);

                $this->createFileFromStub($path."/Controllers/{$version}/{$action}Controller.php", "controller.{$actionLower}", $actionReplacements);
                $this->createFileFromStub($path."/Actions/{$action}{$name}Action.php", "action.{$actionLower}", $actionReplacements);
                $this->createFileFromStub($path."/Payloads/{$version}/{$action}{$name}Payload.php", 'payload', $actionReplacements);
                $this->createFileFromStub($path."/Requests/{$version}/{$action}{$name}Request.php", 'request', $actionReplacements);
            }

            // Show
            $this->createFileFromStub($path."/Controllers/{$version}/ShowController.php", 'controller.show', $replacements);
            $this->createFileFromStub($path."/Actions/Show{$name}Action.php", 'action.show', $replacements);

            // Delete
            $this->createFileFromStub($path."/Controllers/{$version}/DeleteController.php", 'controller.destroy', $replacements);
            $this->createFileFromStub($path."/Actions/Delete{$name}Action.php", 'action.destroy', $replacements);

            // Bulk Delete & Restore
            $this->createFileFromStub($path."/Controllers/{$version}/BulkDeleteController.php", 'controller.bulk-delete', $replacements);
            $this->createFileFromStub($path."/Actions/BulkDelete{$pluralName}Action.php", 'action.bulk-delete', $replacements);
            $this->createFileFromStub($path."/Controllers/{$version}/BulkRestoreController.php", 'controller.bulk-restore', $replacements);
            $this->createFileFromStub($path."/Actions/BulkRestore{$pluralName}Action.php", 'action.bulk-restore', $replacements);
        }

        // Test
        $this->createFileFromStub($path."/Tests/Feature/{$version}/{$name}Test.php", 'test', $replacements);

        if ($options['filter']) {
            $this->createFileFromStub($path."/Filters/{$name}Filter.php", 'filter', $replacements);
        }

        if ($options['migration']) {
            $tableName = $replacements['tableName'];
            $migrationPath = $path.'/Database/Migrations';

            if ($this->option('force')) {
                $files = File::files($migrationPath);
                foreach ($files as $file) {
                    if (str_contains($file->getFilename(), "_create_{$tableName}_table.php")) {
                        File::delete($file->getPathname());
                    }
                }
            }

            $fileName = date('Y_m_d_His')."_create_{$tableName}_table.php";
            $this->createFileFromStub("{$migrationPath}/{$fileName}", 'migration', array_merge($replacements, [
                'idColumn' => $this->getMigrationIdColumn(),

            ]));
        }

        if ($options['factory']) {
            $this->createFileFromStub($path."/Database/Factories/{$name}Factory.php", 'factory', $replacements);
        }

        if ($options['seeder']) {
            $this->createFileFromStub($path."/Database/Seeders/{$name}Seeder.php", 'seeder', $replacements);
        }

        if ($options['event']) {
            $this->createFileFromStub($path."/Events/{$name}Created.php", 'event', $replacements);
        }
    }

    /**
     * @param  array<string, mixed>  $replacements
     */
    protected function createFileFromStub(string $path, string $stub, array $replacements): void
    {
        $stubPath = base_path("resources/stubs/module/{$stub}.stub");
        if (! File::exists($stubPath)) {
            $this->error("Stub not found: {$stubPath}");

            return;
        }

        $content = File::get($stubPath);

        foreach ($replacements as $key => $value) {
            $content = str_replace('{{'.$key.'}}', is_scalar($value) ? (string) $value : '', $content);
        }

        File::put($path, $content);
    }

    /**
     * @param  array<string, bool>  $options
     */
    protected function getRoutesContent(string $name, string $version, array $options): string
    {
        $namespace = "Modules\\{$name}\\Controllers\\{$version}";
        $param = lcfirst($name);
        $slug = Str::kebab(Str::plural($name));

        $uses = [];
        $routeDefs = [];
        $uses[] = "use {$namespace}\\ListController;";

        $routeDefs[] = "    Route::get('/', ListController::class)->name('index');";

        if ($options['action']) {
            $uses[] = "use {$namespace}\\CreateController;";
            $uses[] = "use {$namespace}\\ShowController;";
            $uses[] = "use {$namespace}\\UpdateController;";
            $uses[] = "use {$namespace}\\DeleteController;";
            $uses[] = "use {$namespace}\\BulkDeleteController;";
            $uses[] = "use {$namespace}\\BulkRestoreController;";

            $routeDefs[] = "    Route::post('/', CreateController::class)->name('create');";
            $routeDefs[] = "    Route::get('/{{$param}}', ShowController::class)->name('show');";
            $routeDefs[] = "    Route::put('/{{$param}}', UpdateController::class)->name('update');";
            $routeDefs[] = "    Route::delete('/{{$param}}', DeleteController::class)->name('delete');";
            $routeDefs[] = "    Route::post('/bulk/delete', BulkDeleteController::class)->name('bulk.delete');";
            $routeDefs[] = "    Route::post('/bulk/restore', BulkRestoreController::class)->name('bulk.restore');";
        }

        $uses = array_unique($uses);
        sort($uses);
        $useBlock = implode("\n", $uses);
        $routeBlock = implode("\n", $routeDefs);

        return <<<PHP
use Illuminate\Support\Facades\Route;
{$useBlock}

Route::prefix('{$slug}')->middleware(['auth:sanctum', 'throttle:api'])->name('{$slug}.')->group(function () {
{$routeBlock}
});
PHP;
    }

    /**
     * Get the migration ID column definition (ULID standard).
     */
    protected function getMigrationIdColumn(): string
    {
        return '$table->ulid(\'id\')->primary();';
    }

    /**
     * @param  array<string, bool>  $options
     */
    protected function showSummary(string $name, string $version, array $options): void
    {
        $this->table(
            ['Component', 'Status'],
            [
                ['Module Name', $name],
                ['API Version', $version],
                ['Controllers', 'Created'],
                ['Model', 'Created'],
                ['Repository', $options['repository'] ? 'Created' : 'Skipped'],
                ['Actions', $options['action'] ? 'Created' : 'Skipped'],
                ['Payloads', $options['action'] ? 'Created' : 'Skipped'],
                ['Filter', $options['filter'] ? 'Created' : 'Skipped'],
                ['Migration', $options['migration'] ? 'Created' : 'Skipped'],
                ['Factory', $options['factory'] ? 'Created' : 'Skipped'],
                ['Seeder', $options['seeder'] ? 'Created' : 'Skipped'],
                ['Resource', 'Created'],
                ['Event', $options['event'] ? 'Created' : 'Skipped'],
                ['Tests', 'Created'],
            ]
        );
    }
}
