<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\form;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\table;
use function Laravel\Prompts\task;
use function Laravel\Prompts\text;

#[Signature('make:module {name? : The name of the module} {--force : Overwrite existing files} {--api-version=V1 : API version} {--x|except= : Comma-separated components to skip (action,filter,migration,factory,seeder,event)} {--E|event : Create event} {--a|action : Create CRUD actions & payloads} {--l|filter : Create query filter} {--m|migration : Create migration} {--y|factory : Create factory} {--s|seeder : Create seeder}')]
#[Description('Create a new module with controllers, model, resource, tests, and optional components. Supports shorthand flags (-Ealmys) and --except to skip components.')]
class MakeModuleCommand extends Command
{
    private const array COMPONENTS = [
        'action' => 'CRUD Actions & Payloads',
        'filter' => 'Query Filter',
        'migration' => 'Migration',
        'factory' => 'Factory',
        'seeder' => 'Seeder',
        'event' => 'Event',
    ];

    private const array COMPONENTS_INFO = [
        'action' => 'CRUD actions with payloads for Create, Read, Update, Delete.',
        'filter' => 'Query filtering with sort, search, and pagination support.',
        'migration' => 'Database migration for the module table.',
        'factory' => 'Model factory for testing and seeding.',
        'seeder' => 'Database seeder for development data.',
        'event' => 'Event class fired on module creation.',
    ];

    private const array DEFAULT_COMPONENTS = ['action', 'filter'];

    private const array FIELD_TYPES = [
        'string' => 'String (varchar)',
        'text' => 'Text (longtext)',
        'integer' => 'Integer',
        'boolean' => 'Boolean',
        'date' => 'Date',
        'datetime' => 'DateTime',
        'json' => 'JSON',
        'float' => 'Float (decimal)',
    ];

    private const array ALL_OPERATIONS = [
        'list' => 'List (index)',
        'create' => 'Create',
        'show' => 'Show',
        'update' => 'Update',
        'delete' => 'Delete',
        'bulk-delete' => 'Bulk Delete',
        'bulk-restore' => 'Bulk Restore',
    ];

    private const array DEFAULT_OPERATIONS = ['list', 'create', 'show', 'update', 'delete'];

    public function handle(): void
    {
        $nameArg = is_string($this->argument('name')) ? $this->argument('name') : '';
        $except = (string) $this->option('except');
        $hasFlags = $this->hasComponentFlags();

        if ($except !== '' || $hasFlags) {
            $name = $nameArg !== '' ? $nameArg : suggest(
                label: 'What is the name of the module?',
                options: $this->moduleDirectories(),
                placeholder: 'E.g. Blog',
                required: 'Name is required.',
                validate: fn (string $value) => match (true) {
                    strlen($value) < 3 => 'The name must be at least 3 characters.',
                    strlen($value) > 255 => 'The name must not exceed 255 characters.',
                    ! preg_match('/^[A-Za-z][a-zA-Z0-9]*$/', $value) => 'The name must contain only letters and numbers, and start with a letter.',
                    default => null
                },
                hint: 'Name must start with a letter and contain only alphanumeric characters (e.g., MyName or myName). No spaces or special characters allowed.'
            );

            if ($name === '') {
                error('Module name is required!');

                return;
            }

            $name = Str::studly($name, normalize: true);
            $version = $this->resolveVersion();

            if ($version === null) {
                return;
            }

            if (Storage::disk('modules')->exists($name) && ! $this->option('force')) {
                if (confirm("Module {$name} already exists. Do you want to overwrite it?", default: false) !== true) {
                    info('Aborted.');

                    return;
                }
            }

            $options = $this->resolveNonInteractiveOptions($except);
            $schema = [];
            $operations = array_keys(self::ALL_OPERATIONS);

            $this->generate($name, $version, $schema, $options, $operations, timestamps: true, softDeletes: false);

            return;
        }

        $responses = form()
            ->add(function () use ($nameArg) {
                if ($nameArg !== '') {
                    return $nameArg;
                }

                return suggest(
                    label: 'What is the name of the module?',
                    options: $this->moduleDirectories(),
                    placeholder: 'E.g. Blog',
                    required: 'Name is required.',
                    validate: fn (string $value) => match (true) {
                        strlen($value) < 3 => 'The name must be at least 3 characters.',
                        strlen($value) > 255 => 'The name must not exceed 255 characters.',
                        ! preg_match('/^[A-Za-z][a-zA-Z0-9]*$/', $value) => 'The name must contain only letters and numbers, and start with a letter.',
                        default => null
                    },
                    hint: 'Name must start with a letter and contain only alphanumeric characters (e.g., MyName or myName). No spaces or special characters allowed.'
                );
            }, name: 'name')
            ->add(function (array $responses) {
                $formName = is_string($responses['name']) ? $responses['name'] : '';
                $name = Str::studly($formName, normalize: true);

                if (Storage::disk('modules')->exists($name) && ! $this->option('force')) {
                    return confirm(
                        label: "Module {$name} already exists. Do you want to overwrite it?",
                        default: false,
                    );
                }

                return true;
            }, name: 'overwrite')
            ->add(function () {
                $supported = config('apiroute.supported_versions', ['V1']);
                $cliVersion = $this->option('api-version');
                $default = is_string($cliVersion) && $cliVersion !== ''
                    ? Str::upper($cliVersion)
                    : config('apiroute.default_version', 'V1');

                return suggest(
                    label: 'API version?',
                    options: $supported,
                    default: $default,
                    required: true,
                    validate: fn (string $value) => in_array(Str::upper($value), $supported, true)
                        ? null
                        : 'Unsupported version. Supported: '.implode(', ', $supported),
                );
            }, name: 'apiVersion')
            ->add(function () {
                return confirm(
                    label: 'Include timestamps?',
                    default: true,
                );
            }, name: 'timestamps')
            ->add(function () {
                return confirm(
                    label: 'Include soft deletes?',
                    default: false,
                );
            }, name: 'softDeletes')
            ->add(function () {
                return multiselect(
                    label: 'Which operations to generate?',
                    options: self::ALL_OPERATIONS,
                    default: self::DEFAULT_OPERATIONS,
                    required: 'Select at least one operation.',
                );
            }, name: 'operations')
            ->add(function (array $responses) {
                if ($responses['overwrite'] === false) {
                    return [];
                }

                return multiselect(
                    label: 'Which components would you like to create?',
                    options: self::COMPONENTS,
                    default: self::DEFAULT_COMPONENTS,
                    required: 'Select at least one component.',
                    info: fn (string $key) => self::COMPONENTS_INFO[$key] ?? null,
                );
            }, name: 'components')
            ->submit();

        $name = Str::studly(
            is_string($responses['name']) ? $responses['name'] : '',
            normalize: true,
        );

        if ($responses['overwrite'] === false) {
            info('Aborted.');

            return;
        }

        /** @var array<string, mixed> $responses */
        $version = Str::upper(is_string($responses['apiVersion']) ? $responses['apiVersion'] : '');
        $timestamps = (bool) $responses['timestamps'];
        $softDeletes = (bool) $responses['softDeletes'];
        /** @var array<int, string> $operations */
        $operations = (array) $responses['operations'];

        $componentKeys = (array) $responses['components'];
        $options = [];
        foreach (array_keys(self::COMPONENTS) as $key) {
            $options[$key] = in_array($key, $componentKeys, true);
        }

        $schema = $this->promptSchema();

        $this->generate($name, $version, $schema, $options, $operations, $timestamps, $softDeletes);
    }

    /**
     * @return array<int, array{name: string, type: string, nullable: bool}>
     */
    private function promptSchema(): array
    {
        $fields = [];
        $first = true;

        while (true) {
            if (! $first) {
                if (confirm('Add another field?', default: false) !== true) {
                    break;
                }
            }
            $first = false;

            $name = text(
                label: 'Field name?',
                placeholder: 'E.g. title, body, price',
                required: true,
                validate: fn (string $value) => match (true) {
                    strlen($value) < 2 => 'Field name must be at least 2 characters.',
                    ! preg_match('/^[a-z][a-zA-Z0-9_]*$/', $value) => 'Field name must start with a lowercase letter and contain only letters, numbers, and underscores.',
                    in_array($value, array_column($fields, 'name'), true) => 'Field name must be unique.',
                    default => null
                },
                hint: 'Lowercase, no spaces. E.g. title, price, is_active',
            );

            if ($name === '') {
                break;
            }

            $type = (string) select(
                label: "Field type for '{$name}'?",
                options: self::FIELD_TYPES,
                default: 'string',
            );

            $nullable = (bool) confirm(
                label: "Is '{$name}' nullable?",
                default: false,
            );

            $fields[] = [
                'name' => $name,
                'type' => $type,
                'nullable' => $nullable,
            ];
        }

        if ($fields === []) {
            info('No fields defined. Aborting.');

            return [];
        }

        return $fields;
    }

    /**
     * @param  array<int, array{name: string, type: string, nullable: bool}>  $schema
     * @param  array<string, bool>  $options
     * @param  array<int, string>  $operations
     */
    private function generate(
        string $name,
        string $version,
        array $schema,
        array $options,
        array $operations,
        bool $timestamps,
        bool $softDeletes,
    ): void {
        note("Generating module {$name} ({$version})...");

        $dirsOk = task(
            callback: fn () => $this->createDirectories($name, $version, $options),
            label: 'Creating module directories...',
        );

        if (! $dirsOk) {
            error('Failed to create module directories.');

            return;
        }

        $filesOk = task(
            callback: fn () => $this->createFiles($name, $version, $schema, $options, $operations, $timestamps, $softDeletes),
            label: 'Generating module files...',
        );

        if (! $filesOk) {
            error('Failed to generate module files.');

            return;
        }

        alert("Module {$name} created successfully!");
        $this->showSummary($name, $version, $options, $timestamps, $softDeletes);
    }

    /**
     * @param  array<int, array{name: string, type: string, nullable: bool}>  $schema
     * @param  array<string, bool>  $options
     * @param  array<int, string>  $operations
     */
    private function createFiles(
        string $name,
        string $version,
        array $schema,
        array $options,
        array $operations,
        bool $timestamps,
        bool $softDeletes,
    ): bool {
        $pluralName = Str::plural($name);
        $hasAction = $options['action'] && $this->hasMutateOperations($operations);

        $replacements = [
            'Module' => $name,
            'pluralModule' => $pluralName,
            'Version' => $version,
            'lowerVersion' => Str::lower($version),
            'lowerResource' => Str::camel($name),
            'label' => Str::lower(Str::headline($name)),
            'labelPlural' => Str::lower(Str::headline(Str::plural($name))),
            'slug' => Str::kebab(Str::plural($name)),
            'routePrefix' => Str::lower($version),
            'tableName' => Str::snake(Str::plural($name)),
            'migrationColumns' => $this->renderMigrationColumns($schema),
            'timestamps' => $timestamps ? '$table->timestamps();' : '',
            'softDeletes' => $this->renderSoftDeletes($softDeletes),
            'modelTraits' => $this->renderModelTraits($softDeletes),
            'modelTraitsUse' => $this->renderModelTraitsUse($softDeletes),
            'modelProperties' => $this->renderModelProperties($schema, $timestamps, $softDeletes),
            'fillableColumns' => $this->renderFillableColumns($schema),
            'validationRules' => $this->renderValidationRules($schema),
            'payloadFields' => $this->renderPayloadFields($schema),
            'payloadFromRequest' => $this->renderPayloadFromRequest($schema),
            'payloadToArray' => $this->renderPayloadToArray($schema),
            'factoryFields' => $this->renderFactoryFields($schema),
            'filterFields' => $this->renderFilterFields($schema),
            'filterSorts' => $this->renderFilterSorts($schema),
            'filterAllowedFields' => $this->renderFilterAllowedFields($schema),
            'filterSearchableColumns' => $this->renderFilterSearchableColumns($schema),
            'testCreateData' => $this->renderTestCreateData($schema),
            'testUpdateData' => $this->renderTestUpdateData($schema),
        ];

        if (! $this->putStub("{$name}/Providers/{$name}ServiceProvider.php", 'provider', $replacements)) {
            return false;
        }

        if (! $this->putStub("{$name}/Routes/{$version}.php", 'route', array_merge($replacements, [
            'routesContent' => $this->getRoutesContent($name, $version, $operations),
        ]))) {
            return false;
        }

        if (! $this->putStub("{$name}/Models/{$name}.php", 'model', $replacements)) {
            return false;
        }

        if (in_array('list', $operations, true)) {
            if (! $this->putStub("{$name}/Controllers/{$version}/ListController.php", 'controller.list', $replacements)) {
                return false;
            }

            if (! $this->putStub("{$name}/Requests/{$version}/List{$name}Request.php", 'request.list', $replacements)) {
                return false;
            }
        }

        if (! $this->putStub("{$name}/Resources/{$name}Resource.php", 'resource', $replacements)) {
            return false;
        }

        if ($hasAction) {
            if (in_array('list', $operations, true)) {
                if (! $this->putStub("{$name}/Actions/List{$pluralName}Action.php", 'action.list', $replacements)) {
                    return false;
                }
            }

            foreach (['create', 'update'] as $action) {
                if (! in_array($action, $operations, true)) {
                    continue;
                }

                $actionPascal = Str::studly($action);
                $actionReplacements = array_merge($replacements, ['Action' => $actionPascal]);

                if (! $this->putStub("{$name}/Controllers/{$version}/{$actionPascal}Controller.php", "controller.{$action}", $actionReplacements)) {
                    return false;
                }

                if (! $this->putStub("{$name}/Actions/{$actionPascal}{$name}Action.php", "action.{$action}", $actionReplacements)) {
                    return false;
                }

                if (! $this->putStub("{$name}/Payloads/{$version}/{$actionPascal}{$name}Payload.php", 'payload.mutate', $actionReplacements)) {
                    return false;
                }

                if (! $this->putStub("{$name}/Requests/{$version}/{$actionPascal}{$name}Request.php", 'request.mutate', $actionReplacements)) {
                    return false;
                }
            }

            if (in_array('show', $operations, true)) {
                if (! $this->putStub("{$name}/Controllers/{$version}/ShowController.php", 'controller.show', $replacements)) {
                    return false;
                }

                if (! $this->putStub("{$name}/Actions/Show{$name}Action.php", 'action.show', $replacements)) {
                    return false;
                }
            }

            if (in_array('delete', $operations, true)) {
                if (! $this->putStub("{$name}/Controllers/{$version}/DeleteController.php", 'controller.destroy', $replacements)) {
                    return false;
                }

                if (! $this->putStub("{$name}/Actions/Delete{$name}Action.php", 'action.destroy', $replacements)) {
                    return false;
                }
            }

            if (in_array('bulk-delete', $operations, true)) {
                if (! $this->putStub("{$name}/Controllers/{$version}/BulkDeleteController.php", 'controller.bulk-delete', $replacements)) {
                    return false;
                }

                if (! $this->putStub("{$name}/Actions/BulkDelete{$pluralName}Action.php", 'action.bulk-delete', $replacements)) {
                    return false;
                }
            }

            if (in_array('bulk-restore', $operations, true)) {
                if (! $this->putStub("{$name}/Controllers/{$version}/BulkRestoreController.php", 'controller.bulk-restore', $replacements)) {
                    return false;
                }

                if (! $this->putStub("{$name}/Actions/BulkRestore{$pluralName}Action.php", 'action.bulk-restore', $replacements)) {
                    return false;
                }
            }

            if (! $this->putStub(
                "{$name}/Tests/Unit/{$name}ActionTest.php",
                'test.action-unit',
                $replacements
            )) {
                return false;
            }
        }

        if (! $this->putStub("{$name}/Tests/Feature/{$version}/{$name}Test.php", 'test.feature', $replacements)) {
            return false;
        }

        if ($options['filter'] && ! $this->putStub("{$name}/Filters/{$name}Filter.php", 'filter', $replacements)) {
            return false;
        }

        if ($options['migration']) {
            $tableName = $replacements['tableName'];
            $migrationPath = "{$name}/Database/Migrations";

            if ($this->option('force')) {
                /** @var string[] $files */
                $files = Storage::disk('modules')->files($migrationPath);
                foreach ($files as $file) {
                    /** @var string $file */
                    if (Str::contains($file, "_create_{$tableName}_table.php")) {
                        Storage::disk('modules')->delete($file);
                    }
                }
            }

            $fileName = now()->format('Y_m_d_His')."_create_{$tableName}_table.php";

            if (! $this->putStub("{$migrationPath}/{$fileName}", 'migration', array_merge($replacements, [
                'idColumn' => $this->getMigrationIdColumn(),
            ]))) {
                return false;
            }
        }

        if ($options['factory'] && ! $this->putStub("{$name}/Database/Factories/{$name}Factory.php", 'factory', $replacements)) {
            return false;
        }

        if ($options['seeder'] && ! $this->putStub("{$name}/Database/Seeders/{$name}Seeder.php", 'seeder', $replacements)) {
            return false;
        }

        if ($options['event'] && ! $this->putStub("{$name}/Events/{$name}Created.php", 'event', $replacements)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int, string>  $operations
     */
    private function hasMutateOperations(array $operations): bool
    {
        $mutateOps = ['create', 'update', 'show', 'delete', 'bulk-delete', 'bulk-restore'];

        return array_intersect($operations, $mutateOps) !== [];
    }

    private function resolveVersion(): ?string
    {
        $version = Str::upper((string) $this->option('api-version'));
        $supported = config('apiroute.supported_versions', ['V1']);

        if (! in_array($version, $supported, true)) {
            $formatted = implode(', ', $supported);
            error("Unsupported API version: {$version}. Supported versions: {$formatted}.");

            return null;
        }

        return $version;
    }

    private function hasComponentFlags(): bool
    {
        foreach (array_keys(self::COMPONENTS) as $name) {
            if ($this->option($name) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, bool>
     */
    private function resolveNonInteractiveOptions(string $except): array
    {
        $excepted = $except !== '' ? array_map('trim', explode(',', $except)) : [];

        $valid = array_keys(self::COMPONENTS);

        $unknown = array_diff($excepted, $valid);
        if ($unknown !== []) {
            note('Unknown --except components: '.implode(', ', $unknown).'. Valid components: '.implode(', ', $valid).'.');
        }

        $options = [];
        foreach ($valid as $name) {
            if (in_array($name, $excepted, true)) {
                $options[$name] = false;
            } elseif ($this->option($name) === true) {
                $options[$name] = true;
            } else {
                $options[$name] = false;
            }
        }

        return $options;
    }

    /**
     * @param  array<string, bool>  $options
     */
    private function createDirectories(string $name, string $version, array $options): bool
    {
        $directories = [
            "{$name}/Controllers/{$version}",
            "{$name}/Models",
            "{$name}/Providers",
            "{$name}/Resources",
            "{$name}/Routes",
            "{$name}/Tests/Feature/{$version}",
        ];

        $directories[] = "{$name}/Requests/{$version}";

        if ($options['action']) {
            $directories[] = "{$name}/Actions";
            $directories[] = "{$name}/Payloads/{$version}";
            $directories[] = "{$name}/Tests/Unit";
        }

        if ($options['filter']) {
            $directories[] = "{$name}/Filters";
        }

        if ($options['event']) {
            $directories[] = "{$name}/Events";
        }

        if ($options['migration'] || $options['factory'] || $options['seeder']) {
            $directories[] = "{$name}/Database/Migrations";
            if ($options['factory']) {
                $directories[] = "{$name}/Database/Factories";
            }
            if ($options['seeder']) {
                $directories[] = "{$name}/Database/Seeders";
            }
        }

        foreach ($directories as $dir) {
            if (! Storage::disk('modules')->makeDirectory($dir)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $replacements
     */
    private function putStub(string $relativePath, string $stub, array $replacements): bool
    {
        $stubPath = base_path("resources/stubs/module/{$stub}.stub");
        if (! File::exists($stubPath)) {
            return true;
        }

        $content = File::get($stubPath);

        foreach ($replacements as $key => $value) {
            $content = Str::replace('{{'.$key.'}}', is_scalar($value) ? (string) $value : '', $content);
        }

        $result = Storage::disk('modules')->put($relativePath, $content);

        return $result === true || is_string($result);
    }

    /**
     * @param  array<int, string>  $operations
     */
    private function getRoutesContent(string $name, string $version, array $operations): string
    {
        $namespace = "Modules\\{$name}\\Controllers\\{$version}";
        $param = Str::lcfirst($name);
        $slug = Str::kebab(Str::plural($name));

        $uses = [];
        $routeDefs = [];

        if (in_array('list', $operations, true)) {
            $uses[] = "use {$namespace}\\ListController;";
            $routeDefs[] = "    Route::get('/', ListController::class)->name('index');";
        }

        if (in_array('create', $operations, true)) {
            $uses[] = "use {$namespace}\\CreateController;";
            $routeDefs[] = "    Route::post('/', CreateController::class)->name('create');";
        }

        if (in_array('show', $operations, true)) {
            $uses[] = "use {$namespace}\\ShowController;";
            $routeDefs[] = "    Route::get('/{{$param}}', ShowController::class)->name('show');";
        }

        if (in_array('update', $operations, true)) {
            $uses[] = "use {$namespace}\\UpdateController;";
            $routeDefs[] = "    Route::put('/{{$param}}', UpdateController::class)->name('update');";
        }

        if (in_array('delete', $operations, true)) {
            $uses[] = "use {$namespace}\\DeleteController;";
            $routeDefs[] = "    Route::delete('/{{$param}}', DeleteController::class)->name('delete');";
        }

        if (in_array('bulk-delete', $operations, true)) {
            $uses[] = "use {$namespace}\\BulkDeleteController;";
            $routeDefs[] = "    Route::post('/bulk/delete', BulkDeleteController::class)->name('bulk.delete');";
        }

        if (in_array('bulk-restore', $operations, true)) {
            $uses[] = "use {$namespace}\\BulkRestoreController;";
            $routeDefs[] = "    Route::post('/bulk/restore', BulkRestoreController::class)->name('bulk.restore');";
        }

        $uses = array_unique($uses);
        sort($uses);
        $useBlock = implode("\n", $uses);
        $routeBlock = implode("\n", $routeDefs);

        if ($routeBlock === '') {
            return '';
        }

        return <<<PHP
use Illuminate\Support\Facades\Route;
{$useBlock}

Route::prefix('{$slug}')->middleware(['auth:sanctum', 'throttle:api'])->name('{$slug}.')->group(function () {
{$routeBlock}
});
PHP;
    }

    private function getMigrationIdColumn(): string
    {
        return '$table->ulid(\'id\')->primary();';
    }

    /**
     * @return array<int, string>
     */
    private function moduleDirectories(): array
    {
        /** @var array<int, string> $directories */
        $directories = Storage::disk('modules')->directories();

        return $directories;
    }

    /**
     * @param  array<int, array{name: string, type: string, nullable: bool}>  $schema
     */
    private function renderMigrationColumns(array $schema): string
    {
        return collect($schema)
            ->map(fn (array $field) => $this->migrationColumnLine($field))
            ->implode("\n");
    }

    /**
     * @param  array{name: string, type: string, nullable: bool}  $field
     */
    private function migrationColumnLine(array $field): string
    {
        $col = match ($field['type']) {
            'string' => "\$table->string('{$field['name']}')",
            'text' => "\$table->text('{$field['name']}')",
            'integer' => "\$table->integer('{$field['name']}')",
            'boolean' => "\$table->boolean('{$field['name']}')",
            'date' => "\$table->date('{$field['name']}')",
            'datetime' => "\$table->dateTime('{$field['name']}')",
            'json' => "\$table->json('{$field['name']}')",
            'float' => "\$table->decimal('{$field['name']}', 10, 2)",
            default => "\$table->string('{$field['name']}')",
        };

        if ($field['nullable']) {
            $col .= '->nullable()';
        }

        return "            {$col};";
    }

    private function renderSoftDeletes(bool $softDeletes): string
    {
        if (! $softDeletes) {
            return '';
        }

        return '            $table->softDeletes();';
    }

    private function renderModelTraits(bool $softDeletes): string
    {
        return $softDeletes ? "use Illuminate\Database\Eloquent\SoftDeletes;" : '';
    }

    private function renderModelTraitsUse(bool $softDeletes): string
    {
        return $softDeletes ? ', SoftDeletes' : '';
    }

    /**
     * @param  array<int, array{name: string, type: string, nullable: bool}>  $schema
     */
    private function renderModelProperties(array $schema, bool $timestamps, bool $softDeletes): string
    {
        $lines = [];

        $lines[] = ' * @property string $id The unique identifier (ULID).';

        foreach ($schema as $field) {
            $phpType = match ($field['type']) {
                'string', 'text' => 'string',
                'integer' => 'int',
                'boolean' => 'bool',
                'float' => 'float',
                'json' => 'mixed',
                'date', 'datetime' => 'Carbon\Carbon',
                default => 'string',
            };

            $nullable = $field['nullable'] ? '|null' : '';
            $line = " * @property {$phpType}{$nullable} \${$field['name']}";

            $lines[] = $line;
        }

        if ($timestamps) {
            $lines[] = ' * @property Carbon|null $created_at The timestamp when created.';
            $lines[] = ' * @property Carbon|null $updated_at The timestamp when updated.';
        }

        if ($softDeletes) {
            $lines[] = ' * @property Carbon|null $deleted_at The timestamp when soft deleted.';
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  array<int, array{name: string, type: string, nullable: bool}>  $schema
     */
    private function renderFillableColumns(array $schema): string
    {
        return collect($schema)
            ->map(fn (array $field) => "        '{$field['name']}',")
            ->implode("\n");
    }

    /**
     * @param  array<int, array{name: string, type: string, nullable: bool}>  $schema
     */
    private function renderValidationRules(array $schema): string
    {
        return collect($schema)
            ->map(fn (array $field) => $this->validationRuleLine($field))
            ->implode("\n");
    }

    /**
     * @param  array{name: string, type: string, nullable: bool}  $field
     */
    private function validationRuleLine(array $field): string
    {
        $rules = [];

        if ($field['nullable']) {
            $rules[] = 'sometimes';
            $rules[] = 'nullable';
        } else {
            $rules[] = 'required';
        }

        $rules[] = match ($field['type']) {
            'string' => 'string',
            'text' => 'string',
            'integer' => 'integer',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'date',
            'json' => 'json',
            'float' => 'numeric',
            default => 'string',
        };

        if ($field['type'] === 'string') {
            $rules[] = 'max:255';
        }
        if ($field['type'] === 'text') {
            $rules[] = 'max:65535';
        }
        if ($field['type'] === 'integer') {
            $rules[] = 'min:-2147483648';
            $rules[] = 'max:2147483647';
        }
        if ($field['type'] === 'float') {
            $rules[] = 'min:0';
        }

        $ruleStr = implode("', '", $rules);

        return "            '{$field['name']}' => ['{$ruleStr}'],";
    }

    /**
     * @param  array<int, array{name: string, type: string, nullable: bool}>  $schema
     */
    private function renderPayloadFields(array $schema): string
    {
        return collect($schema)
            ->map(fn (array $field) => $this->payloadFieldLine($field))
            ->implode("\n");
    }

    /**
     * @param  array{name: string, type: string, nullable: bool}  $field
     */
    private function payloadFieldLine(array $field): string
    {
        $phpType = match ($field['type']) {
            'string', 'text' => 'string',
            'integer' => 'int',
            'boolean' => 'bool',
            'float' => 'float',
            'json' => 'mixed',
            'date' => 'string',
            'datetime' => 'string',
            default => 'string',
        };

        $nullable = $field['nullable'] ? '?' : '';
        $default = $field['nullable'] ? ' = null' : '';

        return "        public {$nullable}{$phpType} \${$field['name']}{$default},";
    }

    /**
     * @param  array<int, array{name: string, type: string, nullable: bool}>  $schema
     */
    private function renderPayloadFromRequest(array $schema): string
    {
        return collect($schema)
            ->map(fn (array $field) => $this->payloadFromRequestLine($field))
            ->implode("\n");
    }

    /**
     * @param  array{name: string, type: string, nullable: bool}  $field
     */
    private function payloadFromRequestLine(array $field): string
    {
        if ($field['nullable']) {
            $method = match ($field['type']) {
                'integer' => 'integer',
                'boolean' => 'boolean',
                'float' => 'float',
                'json' => 'input',
                default => 'string',
            };

            if ($method === 'input') {
                return "            {$field['name']}: \$request->input('{$field['name']}'),";
            }

            return "            {$field['name']}: \$request->filled('{$field['name']}') ? \$request->{$method}('{$field['name']}') : null,";
        }

        $method = match ($field['type']) {
            'string', 'text' => "string('{$field['name']}')->toString()",
            'integer' => "integer('{$field['name']}')",
            'boolean' => "boolean('{$field['name']}')",
            'float' => "float('{$field['name']}')",
            'json' => "input('{$field['name']}')",
            default => "string('{$field['name']}')->toString()",
        };

        return "            {$field['name']}: \$request->{$method},";
    }

    /**
     * @param  array<int, array{name: string, type: string, nullable: bool}>  $schema
     */
    private function renderPayloadToArray(array $schema): string
    {
        return collect($schema)
            ->map(fn (array $field) => "            '{$field['name']}' => \$this->{$field['name']},")
            ->implode("\n");
    }

    /**
     * @param  array<int, array{name: string, type: string, nullable: bool}>  $schema
     */
    private function renderFactoryFields(array $schema): string
    {
        return collect($schema)
            ->map(fn (array $field) => $this->factoryFieldLine($field))
            ->implode("\n");
    }

    /**
     * @param  array{name: string, type: string, nullable: bool}  $field
     */
    private function factoryFieldLine(array $field): string
    {
        $faker = match ($field['type']) {
            'string' => 'fake()->word()',
            'text' => 'fake()->sentence()',
            'integer' => 'fake()->randomNumber()',
            'boolean' => 'fake()->boolean()',
            'date' => 'fake()->date()',
            'datetime' => 'fake()->dateTime()',
            'json' => 'fake()->randomElements()',
            'float' => 'fake()->randomFloat(2, 0, 9999)',
            default => 'fake()->word()',
        };

        return "            '{$field['name']}' => {$faker},";
    }

    /**
     * @param  array<int, array{name: string, type: string, nullable: bool}>  $schema
     */
    private function renderFilterFields(array $schema): string
    {
        return collect($schema)
            ->filter(fn (array $f) => $f['type'] === 'string' || $f['type'] === 'text' || $f['type'] === 'integer' || $f['type'] === 'boolean' || $f['type'] === 'float')
            ->map(fn (array $field) => "        '{$field['name']}',")
            ->implode("\n");
    }

    /**
     * @param  array<int, array{name: string, type: string, nullable: bool}>  $schema
     */
    private function renderFilterSorts(array $schema): string
    {
        return collect($schema)
            ->filter(fn (array $f) => $f['type'] !== 'text' && $f['type'] !== 'json')
            ->map(fn (array $field) => "        '{$field['name']}',")
            ->implode("\n");
    }

    /**
     * @param  array<int, array{name: string, type: string, nullable: bool}>  $schema
     */
    private function renderFilterAllowedFields(array $schema): string
    {
        return collect($schema)
            ->map(fn (array $field) => "        '{$field['name']}',")
            ->implode("\n");
    }

    /**
     * @param  array<int, array{name: string, type: string, nullable: bool}>  $schema
     */
    private function renderFilterSearchableColumns(array $schema): string
    {
        return collect($schema)
            ->filter(fn (array $f) => $f['type'] === 'string' || $f['type'] === 'text')
            ->map(fn (array $field) => "        '{$field['name']}',")
            ->implode("\n");
    }

    /**
     * @param  array<int, array{name: string, type: string, nullable: bool}>  $schema
     */
    private function renderTestCreateData(array $schema): string
    {
        return collect($schema)
            ->filter(fn (array $f) => ! $f['nullable'])
            ->map(fn (array $field) => $this->testDataLine($field))
            ->implode("\n");
    }

    /**
     * @param  array<int, array{name: string, type: string, nullable: bool}>  $schema
     */
    private function renderTestUpdateData(array $schema): string
    {
        $required = collect($schema)->filter(fn (array $f) => ! $f['nullable']);

        if ($required->isEmpty()) {
            return '';
        }

        $firstRequired = $required->first();

        return $this->testDataLine($firstRequired, prefix: 'Updated ');
    }

    /**
     * @param  array{name: string, type: string, nullable: bool}  $field
     */
    private function testDataLine(array $field, string $prefix = ''): string
    {
        $value = match ($field['type']) {
            'string' => "'{$prefix}Test {{Module}}'",
            'text' => "'{$prefix}Test content for {{Module}}.'",
            'integer' => '1',
            'boolean' => 'true',
            'float' => '9.99',
            'date' => "'2024-01-01'",
            'datetime' => "'2024-01-01 00:00:00'",
            'json' => "['key' => 'value']",
            default => "'{$prefix}Test'",
        };

        return "        '{$field['name']}' => {$value},";
    }

    /**
     * @param  array<string, bool>  $options
     */
    private function showSummary(
        string $name,
        string $version,
        array $options,
        bool $timestamps,
        bool $softDeletes,
    ): void {
        table(
            ['Component', 'Status'],
            [
                ['Module Name', $name],
                ['API Version', $version],
                ['Timestamps', $timestamps ? 'Yes' : 'No'],
                ['Soft Deletes', $softDeletes ? 'Yes' : 'No'],
                ['Controllers', 'Created'],
                ['Model', 'Created'],
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
