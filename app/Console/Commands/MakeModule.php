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
        $this->createFileFromStub($path."/Providers/{$name}ServiceProvider.php", 'provider', [
            'name' => $name,
            'Version' => $version,
        ]);

        $this->createFileFromStub($path."/Routes/{$version}.php", 'route', [
            'name' => $name,
            'slug' => Str::kebab(Str::plural($name)),
            'routes' => $this->getRoutesContent($name, $version, $options),
            'Version' => $version,
        ]);

        $this->createFileFromStub($path."/Models/{$name}.php", 'model', [
            'name' => $name,
        ]);

        // Index Controller
        $this->createFileFromStub($path."/Controllers/{$version}/IndexController.php", 'controller.index', [
            'Module' => $name,
            'Resource' => $name,
            'Version' => $version,
        ]);

        if ($options['resource']) {
            $this->createFileFromStub($path."/Resources/{$name}Resource.php", 'resource', [
                'name' => $name,
            ]);
        }

        if ($options['action']) {
            // Store
            $this->createFileFromStub($path."/Controllers/{$version}/StoreController.php", 'controller.v1', [
                'Module' => $name,
                'Resource' => $name,
                'Action' => 'Store',
                'Version' => $version,
            ]);
            $this->createFileFromStub($path."/Actions/Store{$name}Action.php", 'action', [
                'Module' => $name,
                'Resource' => $name,
                'Action' => 'Store',
                'Version' => $version,
            ]);
            $this->createFileFromStub($path."/Payloads/{$version}/Store{$name}Payload.php", 'payload', [
                'Module' => $name,
                'Resource' => $name,
                'Action' => 'Store',
                'Version' => $version,
            ]);
            $this->createFileFromStub($path."/Requests/{$version}/Store{$name}Request.php", 'request.v1', [
                'Module' => $name,
                'Resource' => $name,
                'Action' => 'Store',
                'Version' => $version,
            ]);

            // Update
            $this->createFileFromStub($path."/Controllers/{$version}/UpdateController.php", 'controller.v1', [
                'Module' => $name,
                'Resource' => $name,
                'Action' => 'Update',
                'Version' => $version,
            ]);
            $this->createFileFromStub($path."/Actions/Update{$name}Action.php", 'action', [
                'Module' => $name,
                'Resource' => $name,
                'Action' => 'Update',
                'Version' => $version,
            ]);
            $this->createFileFromStub($path."/Payloads/{$version}/Update{$name}Payload.php", 'payload', [
                'Module' => $name,
                'Resource' => $name,
                'Action' => 'Update',
                'Version' => $version,
            ]);
            $this->createFileFromStub($path."/Requests/{$version}/Update{$name}Request.php", 'request.v1', [
                'Module' => $name,
                'Resource' => $name,
                'Action' => 'Update',
                'Version' => $version,
            ]);

            // Show
            $this->createFileFromStub($path."/Controllers/{$version}/ShowController.php", 'controller.show', [
                'Module' => $name,
                'Resource' => $name,
                'lowerResource' => Str::camel($name),
                'Version' => $version,
            ]);

            // Destroy
            $this->createFileFromStub($path."/Controllers/{$version}/DestroyController.php", 'controller.destroy', [
                'Module' => $name,
                'Resource' => $name,
                'lowerResource' => Str::camel($name),
                'Version' => $version,
            ]);
            $this->createFileFromStub($path."/Actions/Destroy{$name}Action.php", 'action.destroy', [
                'Module' => $name,
                'Resource' => $name,
                'lowerResource' => Str::camel($name),
            ]);
        }

        if ($options['filter']) {
            $this->createFileFromStub($path."/Filters/{$name}Filter.php", 'filter', ['name' => $name]);
        }

        if ($options['migration']) {
            $tableName = Str::snake(Str::plural($name));
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
            $this->createFileFromStub("{$migrationPath}/{$fileName}", 'migration', ['tableName' => $tableName]);
        }

        if ($options['factory']) {
            $this->createFileFromStub($path."/Database/Factories/{$name}Factory.php", 'factory', ['name' => $name]);
        }

        if ($options['seeder']) {
            $this->createFileFromStub($path."/Database/Seeders/{$name}Seeder.php", 'seeder', ['name' => $name]);
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

        $routes = "    Route::get('/', {$namespace}\IndexController::class)->name('index');\n";

        if ($options['action']) {
            $routes .= "    Route::post('/', {$namespace}\StoreController::class)->name('store');\n";
            $routes .= "    Route::get('/{{$name}}', {$namespace}\ShowController::class)->name('show');\n";
            $routes .= "    Route::put('/{{$name}}', {$namespace}\UpdateController::class)->name('update');\n";
            $routes .= "    Route::delete('/{{$name}}', {$namespace}\DestroyController::class)->name('destroy');\n";
        }

        return $routes;
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
