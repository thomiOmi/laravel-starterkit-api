<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModule extends Command
{
    protected $signature = 'make:module {name? : The name of the module} {--force : Overwrite existing files}';

    protected $description = 'Create a new module with an interactive and advanced structure';

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
        $modulePath = base_path("modules/{$name}");

        if (File::exists($modulePath) && ! $this->option('force')) {
            if (! $this->confirm("Module {$name} already exists. Do you want to overwrite it?", false)) {
                $this->info('Aborted.');

                return;
            }
        }

        $options = [
            'repository' => (bool) $this->confirm('Create Repository?', true),
            'action' => (bool) $this->confirm('Create CRUD Actions?', true),
            'dto' => (bool) $this->confirm('Create DTO?', true),
            'request' => (bool) $this->confirm('Create Form Request?', true),
            'filter' => (bool) $this->confirm('Create Query Filter?', true),
            'migration' => (bool) $this->confirm('Create Migration?', true),
            'factory' => (bool) $this->confirm('Create Factory?', true),
            'seeder' => (bool) $this->confirm('Create Seeder?', true),
            'resource' => (bool) $this->confirm('Create Resource?', true),
        ];

        $this->info("Generating module {$name}...");

        $this->createDirectories($modulePath, $options);
        $this->createFiles($name, $modulePath, $options);

        $this->info("Module {$name} created successfully!");
        $this->showSummary($name, $options);
    }

    /**
     * @param  array<string, bool>  $options
     */
    protected function createDirectories(string $path, array $options): void
    {
        $directories = [
            'Controllers/V1',
            'Models',
            'Providers',
            'Resources',
            'Routes',
            'Tests/Feature',
        ];

        if ($options['action']) {
            $directories[] = 'Actions';
            $directories[] = 'Payloads/V1';
            $directories[] = 'Requests/V1';
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
    protected function createFiles(string $name, string $path, array $options): void
    {
        $this->createFileFromStub($path."/Providers/{$name}ServiceProvider.php", 'provider', ['name' => $name]);
        $this->createFileFromStub($path.'/Routes/V1.php', 'route', [
            'name' => $name,
            'slug' => Str::kebab(Str::plural($name)),
            'routes' => $this->getRoutesContent($name, $options),
        ]);
        $this->createFileFromStub($path."/Models/{$name}.php", 'model', ['name' => $name]);

        // Single Action Controllers (V1)
        $this->createFileFromStub($path.'/Controllers/V1/IndexController.php', 'controller.v1', [
            'Module' => $name,
            'Resource' => $name,
            'Action' => 'Index',
        ]);

        $this->createFileFromStub($path."/Resources/{$name}Resource.php", 'resource', ['name' => $name]);

        if ($options['action']) {
            $this->createFileFromStub($path."/Actions/Store{$name}Action.php", 'action', [
                'Module' => $name,
                'Resource' => $name,
                'Action' => 'Store',
            ]);

            $this->createFileFromStub($path."/Payloads/V1/Store{$name}Payload.php", 'payload', [
                'Module' => $name,
                'Resource' => $name,
                'Action' => 'Store',
            ]);

            $this->createFileFromStub($path."/Requests/V1/Store{$name}Request.php", 'request.v1', [
                'Module' => $name,
                'Resource' => $name,
                'Action' => 'Store',
            ]);

            $this->createFileFromStub($path.'/Controllers/V1/StoreController.php', 'controller.v1', [
                'Module' => $name,
                'Resource' => $name,
                'Action' => 'Store',
            ]);
        }

        if ($options['request']) {
            $this->createFileFromStub($path."/Requests/{$name}Request.php", 'request', ['name' => $name]);
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
                        $this->warn('Deleted old migration: '.$file->getFilename());
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
        $content = File::get(base_path("resources/stubs/module/{$stub}.stub"));

        foreach ($replacements as $key => $value) {
            $content = str_replace("{{{$key}}}", is_scalar($value) ? (string) $value : '', $content);
        }

        File::put($path, $content);
    }

    /**
     * @param  array<string, bool>  $options
     */
    protected function getRoutesContent(string $name, array $options): string
    {
        $routes = "    Route::get('/', [{$name}Controller::class, 'index'])->name('index');\n";
        if ($options['request']) {
            $routes .= "    Route::post('/', [{$name}Controller::class, 'store'])->name('store');\n";
        }
        $routes .= "    Route::get('/{id}', [{$name}Controller::class, 'show'])->name('show');\n";
        if ($options['request']) {
            $routes .= "    Route::put('/{id}', [{$name}Controller::class, 'update'])->name('update');\n";
        }
        if ($options['action']) {
            $routes .= "    Route::delete('/{id}', [{$name}Controller::class, 'destroy'])->name('destroy');\n";
        }
        if ($options['repository']) {
            $routes .= "    Route::post('/bulk', [{$name}Controller::class, 'bulkAction'])->name('bulk');\n";
        }

        return $routes;
    }

    /**
     * @param  array<string, bool>  $options
     * @return array<string, mixed>
     */
    protected function getControllerData(string $name, array $options): array
    {
        $data = [
            'name' => $name,
            'repoImport' => $options['repository'] ? "use Modules\\{$name}\\Repositories\\{$name}Repository;" : '',
            'filterImport' => $options['filter'] ? "use Modules\\{$name}\\Filters\\{$name}Filter;" : '',
            'dtoImport' => $options['dto'] ? "use Modules\\{$name}\\DTOs\\{$name}DTO;" : '',
            'requestImport' => $options['request'] ? "use Modules\\{$name}\\Requests\\{$name}Request;" : '',
            'resourceImport' => "use Modules\\{$name}\\Resources\\{$name}Resource;",
            'bulkImport' => $options['repository'] ? "use App\Http\Requests\BulkActionRequest;" : '',
            'actionImports' => '',
            'constructor' => '',
            'indexParamsStr' => 'Request $request',
            'indexBody' => '',
            'storeMethod' => '',
            'showMethod' => '',
            'updateMethod' => '',
            'destroyMethod' => '',
            'bulkMethod' => '',
        ];

        if ($options['action']) {
            $data['actionImports'] = "use Modules\\{$name}\\Actions\\Create{$name}Action;\nuse Modules\\{$name}\\Actions\\Update{$name}Action;\nuse Modules\\{$name}\\Actions\\Delete{$name}Action;";
        }

        if ($options['repository']) {
            $data['constructor'] = "    public function __construct(protected {$name}Repository \$repository) {}";
        } else {
            $data['constructor'] = '    public function __construct() {}';
        }

        if ($options['repository']) {
            if ($options['filter']) {
                $data['indexParamsStr'] = "Request \$request, {$name}Filter \$filter";
                $data['indexBody'] = "        \$items = \$this->repository->applyFilter(\$filter)->paginate(\$request->integer('per_page', 10));";
            } else {
                $data['indexBody'] = "        \$items = \$this->repository->paginate(\$request->integer('per_page', 10));";
            }
        } else {
            $data['indexBody'] = '        \$items = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);';
        }

        if ($options['request'] && $options['action']) {
            $data['storeMethod'] = "\n    /**\n     * Store a newly created resource in storage.\n     */\n    public function store({$name}Request \$request, Create{$name}Action \$action): JsonResponse\n    {\n        \$dto = {$name}DTO::fromRequest(\$request);\n        \$item = \$action->execute(\$dto);\n\n        return \$this->successResponse(new {$name}Resource(\$item), '{$name} created successfully', 201);\n    }\n";
            $data['updateMethod'] = "\n    /**\n     * Update the specified resource in storage.\n     */\n    public function update({$name}Request \$request, string|int \$id, Update{$name}Action \$action): JsonResponse\n    {\n        \$dto = {$name}DTO::fromRequest(\$request);\n        \$item = \$action->execute(\$id, \$dto);\n\n        return \$this->successResponse(new {$name}Resource(\$item), '{$name} updated successfully');\n    }\n";
        }

        if ($options['repository']) {
            $data['showMethod'] = "\n    /**\n     * Display the specified resource.\n     */\n    public function show(string|int \$id): JsonResponse\n    {\n        \$item = \$this->repository->findById(\$id);\n\n        return \$this->successResponse(new {$name}Resource(\$item), '{$name} retrieved successfully');\n    }\n";
            $data['bulkMethod'] = "\n    /**\n     * Perform bulk action.\n     */\n    public function bulkAction(BulkActionRequest \$request): JsonResponse\n    {\n        /** @var array{ids: array<int, string|int>, action: string} \$validated */\n        \$validated = \$request->validated();\n\n        \$count = \$this->repository->bulk(\$validated['ids'], \$validated['action']);\n\n        return \$this->successResponse(['count' => \$count], \"Items {\$validated['action']} successfully\");\n    }\n";
        }

        if ($options['action']) {
            $data['destroyMethod'] = "\n    /**\n     * Remove the specified resource from storage.\n     */\n    public function destroy(string|int \$id, Delete{$name}Action \$action): JsonResponse\n    {\n        \$action->execute(\$id);\n\n        return \$this->successResponse(null, '{$name} deleted successfully');\n    }\n";
        }

        return $data;
    }

    /**
     * @param  array<string, bool>  $options
     */
    protected function showSummary(string $name, array $options): void
    {
        $this->table(
            ['Component', 'Status'],
            [
                ['Module Name', $name],
                ['Controller (V1)', 'Created'],
                ['Model', 'Created'],
                ['Repository', $options['repository'] ? 'Created' : 'Skipped'],
                ['Actions (CRUD)', $options['action'] ? 'Created' : 'Skipped'],
                ['DTO', $options['dto'] ? 'Created' : 'Skipped'],
                ['Filter', $options['filter'] ? 'Created' : 'Skipped'],
                ['Migration', $options['migration'] ? 'Created' : 'Skipped'],
                ['Factory', $options['factory'] ? 'Created' : 'Skipped'],
                ['Seeder', $options['seeder'] ? 'Created' : 'Skipped'],
            ]
        );
    }
}
