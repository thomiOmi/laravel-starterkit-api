<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModule extends Command
{
    protected $signature = 'make:module {name? : The name of the module} {--force : Overwrite existing files} {--api-version=V1 : API version}';

    protected $description = 'Create a new module following the API Skill standards';

    public function handle(): void
    {
        $nameArgument = $this->argument('name');
        $name = is_string($nameArgument) ? $nameArgument : '';

        if ($name === '') {
            $askedName = $this->ask('What is the name of the module? (e.g. Blog)');
            $name = is_string($askedName) ? $askedName : '';
        }

        if (! $name) {
            $this->error('Module name is required!');

            return;
        }

        $name = Str::studly($name);
        $version = strtoupper((string) $this->option('api-version'));
        $modulePath = base_path("modules/{$name}");

        if (File::exists($modulePath) && ! $this->option('force')) {
            if (! $this->confirm("Module {$name} already exists. Do you want to overwrite it?", false)) {
                $this->info('Aborted.');

                return;
            }
        }

        $options = [
            'repository' => (bool) $this->confirm('Create Concrete Repository?', true),
            'action' => (bool) $this->confirm('Create CRUD Actions & Payloads?', true),
            'filter' => (bool) $this->confirm('Create Query Filter?', true),
            'migration' => (bool) $this->confirm('Create Migration?', true),
            'factory' => (bool) $this->confirm('Create Factory?', true),
            'seeder' => (bool) $this->confirm('Create Seeder?', true),
            'resource' => (bool) $this->confirm('Create Resource?', true),
        ];

        $this->info("Generating module {$name} ({$version})...");

        $this->createDirectories($modulePath, $version, $options);
        $this->createFiles($name, $modulePath, $version, $options);

        $this->info("Module {$name} created successfully!");
        $this->showSummary($name, $version, $options);
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
        $replacements = [
            'Module' => $name,
            'Resource' => $name,
            'Version' => $version,
            'lowerResource' => Str::camel($name),
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
        $this->createFileFromStub($path."/Controllers/{$version}/IndexController.php", 'controller.index', $replacements);

        if ($options['resource']) {
            $this->createFileFromStub($path."/Resources/{$name}Resource.php", 'resource', $replacements);
        }

        if ($options['action']) {
            // List Action
            $this->createFileFromStub($path."/Actions/List{$name}Action.php", 'action.index', $replacements);

            foreach (['Store', 'Update'] as $action) {
                $actionLower = strtolower($action);
                $actionReplacements = array_merge($replacements, ['Action' => $action]);

                $this->createFileFromStub($path."/Controllers/{$version}/{$action}Controller.php", "controller.{$actionLower}", $actionReplacements);
                $this->createFileFromStub($path."/Actions/{$action}{$name}Action.php", "action.{$actionLower}", $actionReplacements);
                $this->createFileFromStub($path."/Payloads/{$version}/{$action}{$name}Payload.php", 'payload', $actionReplacements);
                $this->createFileFromStub($path."/Requests/{$version}/{$action}{$name}Request.php", 'request.v1', $actionReplacements);
            }

            // Show
            $this->createFileFromStub($path."/Controllers/{$version}/ShowController.php", 'controller.show', $replacements);
            $this->createFileFromStub($path."/Actions/Show{$name}Action.php", 'action.show', $replacements);

            // Destroy
            $this->createFileFromStub($path."/Controllers/{$version}/DestroyController.php", 'controller.destroy', $replacements);
            $this->createFileFromStub($path."/Actions/Destroy{$name}Action.php", 'action.destroy', $replacements);

            // Bulk Delete & Restore
            $this->createFileFromStub($path."/Controllers/{$version}/BulkDeleteController.php", 'controller.bulk-delete', $replacements);
            $this->createFileFromStub($path."/Actions/BulkDelete{$name}Action.php", 'action.bulk-delete', $replacements);
            $this->createFileFromStub($path."/Controllers/{$version}/BulkRestoreController.php", 'controller.bulk-restore', $replacements);
            $this->createFileFromStub($path."/Actions/BulkRestore{$name}Action.php", 'action.bulk-restore', $replacements);
        }

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
            $this->createFileFromStub("{$migrationPath}/{$fileName}", 'migration', $replacements);
        }

        if ($options['factory']) {
            $this->createFileFromStub($path."/Database/Factories/{$name}Factory.php", 'factory', $replacements);
        }

        if ($options['seeder']) {
            $this->createFileFromStub($path."/Database/Seeders/{$name}Seeder.php", 'seeder', $replacements);
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

        $uses[] = "use {$namespace}\\IndexController;";
        $routeDefs[] = "    Route::get('/', IndexController::class)->name('index');";

        if ($options['action']) {
            $uses[] = "use {$namespace}\\StoreController;";
            $uses[] = "use {$namespace}\\ShowController;";
            $uses[] = "use {$namespace}\\UpdateController;";
            $uses[] = "use {$namespace}\\DestroyController;";
            $uses[] = "use {$namespace}\\BulkDeleteController;";
            $uses[] = "use {$namespace}\\BulkRestoreController;";

            $routeDefs[] = "    Route::post('/', StoreController::class)->name('store');";
            $routeDefs[] = "    Route::get('/{{$param}}', ShowController::class)->name('show');";
            $routeDefs[] = "    Route::put('/{{$param}}', UpdateController::class)->name('update');";
            $routeDefs[] = "    Route::delete('/{{$param}}', DestroyController::class)->name('destroy');";
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

Route::prefix('{$slug}')->middleware(['force.json', 'auth:sanctum', 'throttle:api'])->name('{$slug}.')->group(function () {
{$routeBlock}
});
PHP;
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
            ]
        );
    }
}
