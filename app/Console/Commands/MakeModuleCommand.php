<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Help;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Usage;
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
use function Laravel\Prompts\warning;

#[Signature('make:module {name? : The name of the module} {--force : Overwrite existing files} {--api-version=V1 : API version} {--x|except= : Comma-separated components to skip (action,filter,migration,factory,seeder,event)} {--A|add= : Comma-separated components to add to an existing module} {--no-timestamps : Exclude timestamps columns (created_at, updated_at)} {--soft-deletes : Include soft deletes column (deleted_at)} {--T|tests=none : Generate test files (none,unit,feature,all)} {--E|event : Create event} {--a|action : Create CRUD actions & payloads} {--l|filter : Create query filter} {--m|migration : Create migration} {--y|factory : Create factory} {--s|seeder : Create seeder}')]
#[Description('Create a new module with controllers, model, resource, and optional components. Supports shorthand flags (-Talmys), --except to skip components, --add to add components to existing modules, --tests to generate test stubs, --no-timestamps, and --soft-deletes.')]
#[Help('Scaffold a new module with controllers, model, resource, actions, payloads, filter, migration, factory, seeder, event, and tests. Supports interactive and non-interactive modes.')]
#[Usage('make:module Partner')]
#[Usage('make:module Partner --action --filter')]
#[Usage('make:module Partner --except=action,filter')]
#[Usage('make:module Partner --add=migration --no-timestamps')]
#[Usage('make:module Partner -Talmys --api-version=V1')]
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
        $add = (string) $this->option('add');

        if ($add !== '') {
            $this->handleAddMode($nameArg, $add);

            return;
        }

        if ($except !== '' || $this->hasExplicitComponentFlags()) {
            $this->handleNonInteractive($nameArg, $except);

            return;
        }

        $this->handleInteractive($nameArg);
    }

    private function handleNonInteractive(string $nameArg, string $except): void
    {
        $name = $nameArg !== '' ? $nameArg : $this->promptForName($this->existingModuleNames());

        if ($name === '') {
            return;
        }

        $name = Str::studly($name, normalize: true);
        $version = $this->resolveVersion();

        if ($version === null) {
            return;
        }

        if (! $this->confirmOverwrite($name)) {
            return;
        }

        $options = $this->resolveComponentOptions($except);
        $operations = array_keys(self::ALL_OPERATIONS);
        $testsUnit = $this->parseTestsOption('unit');
        $testsFeature = $this->parseTestsOption('feature');

        $this->generate($name, $version, [], $options, $operations, $testsUnit, $testsFeature, timestamps: ! $this->option('no-timestamps'), softDeletes: (bool) $this->option('soft-deletes'));
    }

    private function handleAddMode(string $nameArg, string $add): void
    {
        $components = array_map('trim', explode(',', $add));
        $valid = array_keys(self::COMPONENTS);

        $unknown = array_diff($components, $valid);
        if ($unknown !== []) {
            note('Unknown --add components: '.implode(', ', $unknown).'. Valid: '.implode(', ', $valid).'.');
        }

        $components = array_intersect($components, $valid);

        if ($components === []) {
            error('No valid components to add.');

            return;
        }

        if ($nameArg === '') {
            error('Module name is required when using --add.');

            return;
        }

        $name = Str::studly($nameArg, normalize: true);

        if (! Storage::disk('modules')->exists($name)) {
            error("Module {$name} does not exist. Use make:module without --add to create a new module.");

            return;
        }

        $version = $this->resolveVersion();

        if ($version === null) {
            return;
        }

        $missing = $this->detectMissingComponents($name, $components);

        if ($missing === []) {
            info("All requested components already exist in module {$name}.");

            return;
        }

        note("Adding missing components to module {$name}: ".implode(', ', $missing).'.');

        $options = [];
        foreach ($valid as $key) {
            $options[$key] = in_array($key, $missing, true);
        }

        $hasExistingActions = Storage::disk('modules')->exists("{$name}/Actions");
        $operations = $hasExistingActions
            ? $this->detectExistingOperations($name)
            : array_keys(self::ALL_OPERATIONS);
        $testsUnit = $this->parseTestsOption('unit');
        $testsFeature = $this->parseTestsOption('feature');

        $this->generate($name, $version, [], $options, $operations, $testsUnit, $testsFeature, timestamps: ! $this->option('no-timestamps'), softDeletes: (bool) $this->option('soft-deletes'));
    }

    private function parseTestsOption(string $type): bool
    {
        $value = (string) $this->option('tests');

        return match ($value) {
            'all' => true,
            default => $value === $type,
        };
    }

    private function handleInteractive(string $nameArg): void
    {
        $responses = form()
            ->add(function () use ($nameArg) {
                if ($nameArg !== '') {
                    return $nameArg;
                }

                return $this->promptForName($this->existingModuleNames());
            }, name: 'name')
            ->add(function (array $responses) {
                /** @var array<string, mixed> $responses */
                return $this->formOverwriteResponse($responses);
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
            ->add(function () {
                return select(
                    label: 'Generate test files?',
                    options: [
                        'none' => 'None',
                        'unit' => 'Unit Test',
                        'feature' => 'Feature Test',
                        'all' => 'Both',
                    ],
                    default: 'none',
                );
            }, name: 'tests')
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

        $testsValue = is_string($responses['tests']) ? $responses['tests'] : 'none';
        $testsUnit = $testsValue === 'unit' || $testsValue === 'all';
        $testsFeature = $testsValue === 'feature' || $testsValue === 'all';

        $schema = $this->promptForFields();

        $this->generate($name, $version, $schema, $options, $operations, $testsUnit, $testsFeature, $timestamps, $softDeletes);
    }

    /**
     * @return array<int, string>
     */
    private function existingModuleNames(): array
    {
        /** @var array<int, string> $dirs */
        $dirs = Storage::disk('modules')->directories();

        return array_values(array_map(fn (string $path) => basename($path), $dirs));
    }

    /**
     * @param  array<int, string>  $existing
     */
    private function promptForName(array $existing): string
    {
        return (string) suggest(
            label: 'What is the module name?',
            options: fn (string $value) => $value !== ''
                ? array_values(array_filter(
                    $existing,
                    fn (mixed $name) => str_contains(Str::lower((string) $name), Str::lower($value)),
                ))
                : [],
            placeholder: 'E.g. Partner, Category',
            required: true,
            hint: 'Type the module name. Existing modules shown as suggestions.',
        );
    }

    private function confirmOverwrite(string $name): bool
    {
        if ($this->option('force') || ! Storage::disk('modules')->exists($name)) {
            return true;
        }

        info("Module {$name} already exists.");

        return (bool) confirm('Overwrite existing module?', default: false);
    }

    /**
     * @param  array<string, mixed>  $responses
     */
    private function formOverwriteResponse(array $responses): bool
    {
        $name = is_string($responses['name']) ? $responses['name'] : '';
        $studly = Str::studly($name, normalize: true);

        if ($this->option('force') || ! Storage::disk('modules')->exists($studly)) {
            return true;
        }

        warning("Module {$studly} already exists.");

        return (bool) confirm('Overwrite existing module?', default: false);
    }

    /**
     * @param  array<int, string>  $components
     * @return array<int, string>
     */
    private function detectMissingComponents(string $name, array $components): array
    {
        return array_values(array_filter(
            $components,
            fn (string $key) => match ($key) {
                'action' => ! Storage::disk('modules')->exists("{$name}/Actions"),
                'filter' => ! Storage::disk('modules')->exists("{$name}/Filters"),
                'migration' => ! Storage::disk('modules')->exists("{$name}/Database/Migrations"),
                'factory' => ! Storage::disk('modules')->exists("{$name}/Database/Factories"),
                'seeder' => ! Storage::disk('modules')->exists("{$name}/Database/Seeders"),
                'event' => ! Storage::disk('modules')->exists("{$name}/Events"),
                default => false,
            },
        ));
    }

    /**
     * @return array<int, string>
     */
    private function detectExistingOperations(string $name): array
    {
        $existing = [];
        $actionDir = "{$name}/Actions";
        /** @var array<int, string> $files */
        $files = Storage::disk('modules')->files($actionDir);

        $patterns = [
            'list' => '/List.*Action\.php$/',
            'create' => '/Create.*Action\.php$/',
            'show' => '/Show.*Action\.php$/',
            'update' => '/Update.*Action\.php$/',
            'delete' => '/Delete.*Action\.php$/',
            'bulk-delete' => '/BulkDelete.*Action\.php$/',
            'bulk-restore' => '/BulkRestore.*Action\.php$/',
        ];

        foreach ($patterns as $operation => $pattern) {
            foreach ($files as $file) {
                if (preg_match($pattern, $file)) {
                    $existing[] = $operation;
                    break;
                }
            }
        }

        return $existing;
    }

    /**
     * @return array<int, array{name: string, type: string, nullable: bool}>
     */
    private function promptForFields(): array
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
        bool $testsUnit,
        bool $testsFeature,
        bool $timestamps,
        bool $softDeletes,
    ): void {
        note("Generating module {$name} ({$version})...");

        $dirsOk = task(
            callback: fn () => $this->createDirectories($name, $version, $options, $testsUnit, $testsFeature),
            label: 'Creating module directories...',
        );

        if (! $dirsOk) {
            error('Failed to create module directories.');

            return;
        }

        $filesOk = task(
            callback: fn () => $this->createFiles($name, $version, $schema, $options, $operations, $testsUnit, $testsFeature, $timestamps, $softDeletes),
            label: 'Generating module files...',
        );

        if (! $filesOk) {
            error('Failed to generate module files.');

            return;
        }

        alert("Module {$name} created successfully!");
        $this->showSummary($name, $version, $options, $testsUnit, $testsFeature, $timestamps, $softDeletes);
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
        bool $testsUnit,
        bool $testsFeature,
        bool $timestamps,
        bool $softDeletes,
    ): bool {
        $pluralName = Str::plural($name);
        $hasAction = $options['action'] && $this->hasActionableOperations($operations);

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
            'resourceTimestamps' => $timestamps
                ? "            'created_at' => \$this->resource->created_at,\n            'updated_at' => \$this->resource->updated_at,\n"
                : '',
            'filterTimestamps' => $timestamps
                ? "        'created_at',\n        'updated_at',\n"
                : '',
        ];

        if (! $softDeletes) {
            $operations = array_values(array_filter(
                $operations,
                fn (string $op) => $op !== 'bulk-restore',
            ));
        }

        foreach ([
            'createCoreFiles',
            'createActionFiles',
            'createControllerAndResourceFiles',
            'createOptionalFiles',
        ] as $method) {
            if (! $this->{$method}($name, $version, $replacements, $options, $operations, $hasAction)) {
                return false;
            }
        }

        if (! $this->createTestFiles($name, $version, $replacements, $options, $operations, $hasAction, $testsUnit, $testsFeature)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, string>  $replacements
     * @param  array<string, bool>  $options
     * @param  array<int, string>  $operations
     */
    private function createCoreFiles(
        string $name,
        string $version,
        array $replacements,
        array $options,
        array $operations,
        bool $hasAction,
    ): bool {
        if (! $this->putStub("{$name}/Providers/{$name}ServiceProvider.php", 'provider', $replacements)) {
            return false;
        }

        if (! $this->putStub("{$name}/Routes/{$version}.php", 'route', array_merge($replacements, [
            'routesContent' => $this->buildRoutesContent($name, $version, $operations, $hasAction),
        ]))) {
            return false;
        }

        if (! $this->putStub("{$name}/Models/{$name}.php", 'model', $replacements)) {
            return false;
        }

        if (! $this->putStub("{$name}/Resources/{$name}Resource.php", 'resource', $replacements)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, string>  $replacements
     * @param  array<string, bool>  $options
     * @param  array<int, string>  $operations
     */
    private function createControllerAndResourceFiles(
        string $name,
        string $version,
        array $replacements,
        array $options,
        array $operations,
        bool $hasAction,
    ): bool {
        if (! $hasAction) {
            return true;
        }

        if (in_array('list', $operations, true)) {
            if (! $this->putStub("{$name}/Controllers/{$version}/ListController.php", 'controller.list', $replacements)) {
                return false;
            }

            if (! $this->putStub("{$name}/Requests/{$version}/List{$name}Request.php", 'request.list', $replacements)) {
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
        }

        if (in_array('show', $operations, true)) {
            if (! $this->putStub("{$name}/Controllers/{$version}/ShowController.php", 'controller.show', $replacements)) {
                return false;
            }
        }

        if (in_array('delete', $operations, true)) {
            if (! $this->putStub("{$name}/Controllers/{$version}/DeleteController.php", 'controller.destroy', $replacements)) {
                return false;
            }
        }

        if (in_array('bulk-delete', $operations, true)) {
            if (! $this->putStub("{$name}/Controllers/{$version}/BulkDeleteController.php", 'controller.bulk-delete', $replacements)) {
                return false;
            }
        }

        if (in_array('bulk-restore', $operations, true)) {
            if (! $this->putStub("{$name}/Controllers/{$version}/BulkRestoreController.php", 'controller.bulk-restore', $replacements)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, string>  $replacements
     * @param  array<string, bool>  $options
     * @param  array<int, string>  $operations
     */
    private function createActionFiles(
        string $name,
        string $version,
        array $replacements,
        array $options,
        array $operations,
        bool $hasAction,
    ): bool {
        if (! $hasAction) {
            return true;
        }

        $pluralName = Str::plural($name);

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
            if (! $this->putStub("{$name}/Actions/Show{$name}Action.php", 'action.show', $replacements)) {
                return false;
            }
        }

        if (in_array('delete', $operations, true)) {
            if (! $this->putStub("{$name}/Actions/Delete{$name}Action.php", 'action.destroy', $replacements)) {
                return false;
            }
        }

        if (in_array('bulk-delete', $operations, true)) {
            if (! $this->putStub("{$name}/Actions/BulkDelete{$pluralName}Action.php", 'action.bulk-delete', $replacements)) {
                return false;
            }
        }

        if (in_array('bulk-restore', $operations, true)) {
            if (! $this->putStub("{$name}/Actions/BulkRestore{$pluralName}Action.php", 'action.bulk-restore', $replacements)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, string>  $replacements
     * @param  array<string, bool>  $options
     * @param  array<int, string>  $operations
     */
    private function createTestFiles(
        string $name,
        string $version,
        array $replacements,
        array $options,
        array $operations,
        bool $hasAction,
        bool $testsUnit,
        bool $testsFeature,
    ): bool {
        if ($testsUnit && ! $this->putStub("{$name}/Tests/Unit/{$name}UnitTest.php", 'test.unit', $replacements)) {
            return false;
        }

        if ($testsFeature && ! $this->putStub("{$name}/Tests/Feature/{$version}/{$name}Test.php", 'test.feature', $replacements)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, string>  $replacements
     * @param  array<string, bool>  $options
     * @param  array<int, string>  $operations
     */
    private function createOptionalFiles(
        string $name,
        string $version,
        array $replacements,
        array $options,
        array $operations,
        bool $hasAction,
    ): bool {
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
                'idColumn' => '$table->ulid(\'id\')->primary();',
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

        if ($options['event'] && ! $this->putStub("{$name}/Events/{$name}Event.php", 'event', $replacements)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int, string>  $operations
     */
    private function hasActionableOperations(array $operations): bool
    {
        $writeOps = ['create', 'update', 'show', 'delete', 'bulk-delete', 'bulk-restore'];

        return array_intersect($operations, $writeOps) !== [];
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

    private function hasExplicitComponentFlags(): bool
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
    private function resolveComponentOptions(string $except): array
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
    private function createDirectories(string $name, string $version, array $options, bool $testsUnit, bool $testsFeature): bool
    {
        $directories = [
            "{$name}/Controllers/{$version}",
            "{$name}/Models",
            "{$name}/Providers",
            "{$name}/Resources",
            "{$name}/Routes",
        ];

        if ($testsFeature) {
            $directories[] = "{$name}/Tests/Feature/{$version}";
        }

        $directories[] = "{$name}/Requests/{$version}";

        if ($testsUnit) {
            $directories[] = "{$name}/Tests/Unit";
        }

        if ($options['action']) {
            $directories[] = "{$name}/Actions";
            $directories[] = "{$name}/Payloads/{$version}";
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
    private function buildRoutesContent(string $name, string $version, array $operations, bool $hasAction): string
    {
        if (! $hasAction) {
            return '';
        }

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
    /**
     * @param  array<string, bool>  $options
     */
    private function showSummary(
        string $name,
        string $version,
        array $options,
        bool $testsUnit,
        bool $testsFeature,
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
                ['Controllers', $options['action'] ? 'Created' : 'Skipped'],
                ['Model', 'Created'],
                ['Actions', $options['action'] ? 'Created' : 'Skipped'],
                ['Payloads', $options['action'] ? 'Created' : 'Skipped'],
                ['Filter', $options['filter'] ? 'Created' : 'Skipped'],
                ['Migration', $options['migration'] ? 'Created' : 'Skipped'],
                ['Factory', $options['factory'] ? 'Created' : 'Skipped'],
                ['Seeder', $options['seeder'] ? 'Created' : 'Skipped'],
                ['Resource', 'Created'],
                ['Event', $options['event'] ? 'Created' : 'Skipped'],
                ['Unit Test', $testsUnit ? 'Created' : 'Skipped'],
                ['Feature Test', $testsFeature ? 'Created' : 'Skipped'],
            ]
        );
    }
}
