<?php

declare(strict_types=1);

use App\Console\Commands\MakeModuleCommand;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

covers(MakeModuleCommand::class);

const MAKE_MODULE = 'TestMod';

beforeEach(function () {
    Storage::fake('modules');
});

describe('basic creation', function () {
    it('can create a module with name argument', function () {
        artisanCommand($this, 'make:module', ['name' => MAKE_MODULE])
            ->expectsQuestion('API version?', 'V1')
            ->expectsQuestion('Include timestamps?', true)
            ->expectsQuestion('Include soft deletes?', false)
            ->expectsChoice('Which operations to generate?', ['list', 'create', 'show', 'update', 'delete'], [
                'list' => 'List (index)',
                'create' => 'Create',
                'show' => 'Show',
                'update' => 'Update',
                'delete' => 'Delete',
                'bulk-delete' => 'Bulk Delete',
                'bulk-restore' => 'Bulk Restore',
            ])
            ->expectsChoice(
                'Which components would you like to create?',
                ['action', 'filter'],
                ['action' => 'CRUD Actions & Payloads', 'filter' => 'Query Filter', 'migration' => 'Migration', 'factory' => 'Factory', 'seeder' => 'Seeder', 'event' => 'Event'],
            )
            ->expectsChoice('Generate test files?', 'none', [
                'none' => 'None',
                'unit' => 'Unit Test',
                'feature' => 'Feature Test',
                'all' => 'Both',
            ])
            ->expectsQuestion('Field name?', 'name')
            ->expectsChoice("Field type for 'name'?", 'string', [
                'string' => 'String (varchar)',
                'text' => 'Text (longtext)',
                'integer' => 'Integer',
                'boolean' => 'Boolean',
                'date' => 'Date',
                'datetime' => 'DateTime',
                'json' => 'JSON',
                'float' => 'Float (decimal)',
            ])
            ->expectsQuestion("Is 'name' nullable?", false)
            ->expectsQuestion('Add another field?', false)
            ->assertSuccessful();

        Storage::disk('modules')->assertExists(MAKE_MODULE);
        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Providers/'.MAKE_MODULE.'ServiceProvider.php');
        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Routes/V1.php');
        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Models/'.MAKE_MODULE.'.php');
        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Controllers/V1/ListController.php');
        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Requests/V1/List'.MAKE_MODULE.'Request.php');
        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Resources/'.MAKE_MODULE.'Resource.php');
    });

    it('prompts for missing name', function () {
        artisanCommand($this, 'make:module')
            ->expectsQuestion('What is the module name?', MAKE_MODULE)
            ->expectsQuestion('API version?', 'V1')
            ->expectsQuestion('Include timestamps?', true)
            ->expectsQuestion('Include soft deletes?', false)
            ->expectsChoice('Which operations to generate?', ['list', 'create', 'show', 'update', 'delete'], [
                'list' => 'List (index)',
                'create' => 'Create',
                'show' => 'Show',
                'update' => 'Update',
                'delete' => 'Delete',
                'bulk-delete' => 'Bulk Delete',
                'bulk-restore' => 'Bulk Restore',
            ])
            ->expectsChoice(
                'Which components would you like to create?',
                ['action', 'filter'],
                ['action' => 'CRUD Actions & Payloads', 'filter' => 'Query Filter', 'migration' => 'Migration', 'factory' => 'Factory', 'seeder' => 'Seeder', 'event' => 'Event'],
            )
            ->expectsChoice('Generate test files?', 'none', [
                'none' => 'None',
                'unit' => 'Unit Test',
                'feature' => 'Feature Test',
                'all' => 'Both',
            ])
            ->expectsQuestion('Field name?', 'name')
            ->expectsChoice("Field type for 'name'?", 'string', [
                'string' => 'String (varchar)',
                'text' => 'Text (longtext)',
                'integer' => 'Integer',
                'boolean' => 'Boolean',
                'date' => 'Date',
                'datetime' => 'DateTime',
                'json' => 'JSON',
                'float' => 'Float (decimal)',
            ])
            ->expectsQuestion("Is 'name' nullable?", false)
            ->expectsQuestion('Add another field?', false)
            ->assertSuccessful();

        Storage::disk('modules')->assertExists(MAKE_MODULE);
    });
});

describe('existing module', function () {
    it('aborts when module exists without force', function () {
        Storage::disk('modules')->makeDirectory(MAKE_MODULE);

        artisanCommand($this, 'make:module', ['name' => MAKE_MODULE])
            ->expectsQuestion('Overwrite existing module?', false)
            ->expectsQuestion('API version?', 'V1')
            ->expectsQuestion('Include timestamps?', true)
            ->expectsQuestion('Include soft deletes?', false)
            ->expectsChoice('Which operations to generate?', ['list', 'create', 'show', 'update', 'delete'], [
                'list' => 'List (index)',
                'create' => 'Create',
                'show' => 'Show',
                'update' => 'Update',
                'delete' => 'Delete',
                'bulk-delete' => 'Bulk Delete',
                'bulk-restore' => 'Bulk Restore',
            ])
            ->expectsChoice('Generate test files?', 'none', [
                'none' => 'None',
                'unit' => 'Unit Test',
                'feature' => 'Feature Test',
                'all' => 'Both',
            ])
            ->assertSuccessful();

        expect(Storage::disk('modules')->exists(MAKE_MODULE.'/Providers'))->toBeFalse();
    });

    it('creates module with force flag when exists', function () {
        Storage::disk('modules')->makeDirectory(MAKE_MODULE.'/Models');
        Storage::disk('modules')->put(MAKE_MODULE.'/Models/'.MAKE_MODULE.'.php', '<?php // old content');

        artisanCommand($this, 'make:module', [
            'name' => MAKE_MODULE,
            '--force' => true,
        ])
            ->expectsQuestion('API version?', 'V1')
            ->expectsQuestion('Include timestamps?', true)
            ->expectsQuestion('Include soft deletes?', false)
            ->expectsChoice('Which operations to generate?', ['list', 'create', 'show', 'update', 'delete'], [
                'list' => 'List (index)',
                'create' => 'Create',
                'show' => 'Show',
                'update' => 'Update',
                'delete' => 'Delete',
                'bulk-delete' => 'Bulk Delete',
                'bulk-restore' => 'Bulk Restore',
            ])
            ->expectsChoice(
                'Which components would you like to create?',
                ['action', 'filter'],
                ['action' => 'CRUD Actions & Payloads', 'filter' => 'Query Filter', 'migration' => 'Migration', 'factory' => 'Factory', 'seeder' => 'Seeder', 'event' => 'Event'],
            )
            ->expectsChoice('Generate test files?', 'none', [
                'none' => 'None',
                'unit' => 'Unit Test',
                'feature' => 'Feature Test',
                'all' => 'Both',
            ])
            ->expectsQuestion('Field name?', 'name')
            ->expectsChoice("Field type for 'name'?", 'string', [
                'string' => 'String (varchar)',
                'text' => 'Text (longtext)',
                'integer' => 'Integer',
                'boolean' => 'Boolean',
                'date' => 'Date',
                'datetime' => 'DateTime',
                'json' => 'JSON',
                'float' => 'Float (decimal)',
            ])
            ->expectsQuestion("Is 'name' nullable?", false)
            ->expectsQuestion('Add another field?', false)
            ->assertSuccessful();

        Storage::disk('modules')->assertExists(MAKE_MODULE);
    });
});

describe('component options', function () {
    it('respects --except flag', function () {
        artisanCommand($this, 'make:module', [
            'name' => MAKE_MODULE,
            '--except' => 'action,filter,migration,factory,seeder,event',
        ])
            ->assertSuccessful();

        Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Actions');
        Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Controllers/V1/ListController.php');
        Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Filters');
        Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Database/Migrations');
        Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Database/Factories');
        Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Database/Seeders');
        Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Events');
    });

    it('creates optional components when flags are passed', function () {
        artisanCommand($this, 'make:module', [
            'name' => MAKE_MODULE,
            '--action' => true,
            '--filter' => true,
            '--migration' => true,
            '--factory' => true,
            '--seeder' => true,
            '--event' => true,
        ])
            ->assertSuccessful();

        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Actions');
        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Filters/'.MAKE_MODULE.'Filter.php');
        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Database/Migrations');
        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Database/Factories');
        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Database/Seeders');
        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Events/'.MAKE_MODULE.'Event.php');
    });

    it('warns about unknown --except components', function () {
        artisanCommand($this, 'make:module', [
            'name' => MAKE_MODULE,
            '--except' => 'unknown,action',
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Unknown --except components: unknown');

        Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Actions');
    });
});

describe('--add component', function () {
    it('adds missing filter component to existing module', function () {
        artisanCommand($this, 'make:module', ['name' => MAKE_MODULE])
            ->expectsQuestion('API version?', 'V1')
            ->expectsQuestion('Include timestamps?', true)
            ->expectsQuestion('Include soft deletes?', false)
            ->expectsChoice('Which operations to generate?', ['list', 'create', 'show', 'update', 'delete'], [
                'list' => 'List (index)',
                'create' => 'Create',
                'show' => 'Show',
                'update' => 'Update',
                'delete' => 'Delete',
                'bulk-delete' => 'Bulk Delete',
                'bulk-restore' => 'Bulk Restore',
            ])
            ->expectsChoice(
                'Which components would you like to create?',
                ['action'],
                ['action' => 'CRUD Actions & Payloads', 'filter' => 'Query Filter', 'migration' => 'Migration', 'factory' => 'Factory', 'seeder' => 'Seeder', 'event' => 'Event'],
            )
            ->expectsChoice('Generate test files?', 'none', [
                'none' => 'None',
                'unit' => 'Unit Test',
                'feature' => 'Feature Test',
                'all' => 'Both',
            ])
            ->expectsQuestion('Field name?', 'name')
            ->expectsChoice("Field type for 'name'?", 'string', [
                'string' => 'String (varchar)',
                'text' => 'Text (longtext)',
                'integer' => 'Integer',
                'boolean' => 'Boolean',
                'date' => 'Date',
                'datetime' => 'DateTime',
                'json' => 'JSON',
                'float' => 'Float (decimal)',
            ])
            ->expectsQuestion("Is 'name' nullable?", false)
            ->expectsQuestion('Add another field?', false)
            ->assertSuccessful();

        Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Filters/'.MAKE_MODULE.'Filter.php');

        artisanCommand($this, 'make:module', [
            'name' => MAKE_MODULE,
            '--add' => 'filter',
        ])->assertSuccessful();

        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Filters/'.MAKE_MODULE.'Filter.php');
    });

    it('warns about already-existing components', function () {
        artisanCommand($this, 'make:module', ['name' => MAKE_MODULE])
            ->expectsQuestion('API version?', 'V1')
            ->expectsQuestion('Include timestamps?', true)
            ->expectsQuestion('Include soft deletes?', false)
            ->expectsChoice('Which operations to generate?', ['list', 'create', 'show', 'update', 'delete'], [
                'list' => 'List (index)',
                'create' => 'Create',
                'show' => 'Show',
                'update' => 'Update',
                'delete' => 'Delete',
                'bulk-delete' => 'Bulk Delete',
                'bulk-restore' => 'Bulk Restore',
            ])
            ->expectsChoice(
                'Which components would you like to create?',
                ['action', 'filter'],
                ['action' => 'CRUD Actions & Payloads', 'filter' => 'Query Filter', 'migration' => 'Migration', 'factory' => 'Factory', 'seeder' => 'Seeder', 'event' => 'Event'],
            )
            ->expectsChoice('Generate test files?', 'none', [
                'none' => 'None',
                'unit' => 'Unit Test',
                'feature' => 'Feature Test',
                'all' => 'Both',
            ])
            ->expectsQuestion('Field name?', 'name')
            ->expectsChoice("Field type for 'name'?", 'string', [
                'string' => 'String (varchar)',
                'text' => 'Text (longtext)',
                'integer' => 'Integer',
                'boolean' => 'Boolean',
                'date' => 'Date',
                'datetime' => 'DateTime',
                'json' => 'JSON',
                'float' => 'Float (decimal)',
            ])
            ->expectsQuestion("Is 'name' nullable?", false)
            ->expectsQuestion('Add another field?', false)
            ->assertSuccessful();

        artisanCommand($this, 'make:module', [
            'name' => MAKE_MODULE,
            '--add' => 'filter',
        ])->expectsOutputToContain('All requested components already exist');
    });

    it('requires name argument', function () {
        artisanCommand($this, 'make:module', ['--add' => 'filter'])
            ->expectsOutputToContain('Module name is required');
    });

    it('fails for non-existent module', function () {
        artisanCommand($this, 'make:module', [
            'name' => 'NonExistent',
            '--add' => 'filter',
        ])->expectsOutputToContain('does not exist');
    });

    it('warns about unknown component', function () {
        Storage::disk('modules')->makeDirectory(MAKE_MODULE);

        artisanCommand($this, 'make:module', [
            'name' => MAKE_MODULE,
            '--add' => 'unknown,filter',
        ])->assertSuccessful();
    });
});

describe('api version', function () {
    it('uses api-version option correctly', function () {
        Config::set('apiroute.supported_versions', ['V1', 'V2']);

        artisanCommand($this, 'make:module', [
            'name' => MAKE_MODULE,
            '--api-version' => 'V2',
        ])
            ->expectsQuestion('API version?', 'V2')
            ->expectsQuestion('Include timestamps?', true)
            ->expectsQuestion('Include soft deletes?', false)
            ->expectsChoice('Which operations to generate?', ['list', 'create', 'show', 'update', 'delete'], [
                'list' => 'List (index)',
                'create' => 'Create',
                'show' => 'Show',
                'update' => 'Update',
                'delete' => 'Delete',
                'bulk-delete' => 'Bulk Delete',
                'bulk-restore' => 'Bulk Restore',
            ])
            ->expectsChoice(
                'Which components would you like to create?',
                ['action', 'filter'],
                ['action' => 'CRUD Actions & Payloads', 'filter' => 'Query Filter', 'migration' => 'Migration', 'factory' => 'Factory', 'seeder' => 'Seeder', 'event' => 'Event'],
            )
            ->expectsChoice('Generate test files?', 'none', [
                'none' => 'None',
                'unit' => 'Unit Test',
                'feature' => 'Feature Test',
                'all' => 'Both',
            ])
            ->expectsQuestion('Field name?', 'name')
            ->expectsChoice("Field type for 'name'?", 'string', [
                'string' => 'String (varchar)',
                'text' => 'Text (longtext)',
                'integer' => 'Integer',
                'boolean' => 'Boolean',
                'date' => 'Date',
                'datetime' => 'DateTime',
                'json' => 'JSON',
                'float' => 'Float (decimal)',
            ])
            ->expectsQuestion("Is 'name' nullable?", false)
            ->expectsQuestion('Add another field?', false)
            ->assertSuccessful();

        $provider = Storage::disk('modules')->get(MAKE_MODULE.'/Providers/'.MAKE_MODULE.'ServiceProvider.php');
        expect($provider)->toContain('class '.MAKE_MODULE.'ServiceProvider extends ServiceProvider')
            ->not->toContain('configureRoutes');

        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Routes/V2.php');
    });

    it('rejects invalid api-version format', function () {
        artisanCommand($this, 'make:module', [
            'name' => 'VersionTest',
            '--except' => 'action,filter,migration,factory,seeder,event',
            '--api-version' => 'v_1',
        ])
            ->expectsOutputToContain('Unsupported API version: V_1');

        Storage::disk('modules')->assertMissing('VersionTest');
    });

    it('accepts lowercase api-version and uppercases it', function () {
        artisanCommand($this, 'make:module', [
            'name' => MAKE_MODULE,
            '--api-version' => 'v1',
        ])
            ->expectsQuestion('API version?', 'v1')
            ->expectsQuestion('Include timestamps?', true)
            ->expectsQuestion('Include soft deletes?', false)
            ->expectsChoice('Which operations to generate?', ['list', 'create', 'show', 'update', 'delete'], [
                'list' => 'List (index)',
                'create' => 'Create',
                'show' => 'Show',
                'update' => 'Update',
                'delete' => 'Delete',
                'bulk-delete' => 'Bulk Delete',
                'bulk-restore' => 'Bulk Restore',
            ])
            ->expectsChoice(
                'Which components would you like to create?',
                ['action', 'filter'],
                ['action' => 'CRUD Actions & Payloads', 'filter' => 'Query Filter', 'migration' => 'Migration', 'factory' => 'Factory', 'seeder' => 'Seeder', 'event' => 'Event'],
            )
            ->expectsChoice('Generate test files?', 'none', [
                'none' => 'None',
                'unit' => 'Unit Test',
                'feature' => 'Feature Test',
                'all' => 'Both',
            ])
            ->expectsQuestion('Field name?', 'name')
            ->expectsChoice("Field type for 'name'?", 'string', [
                'string' => 'String (varchar)',
                'text' => 'Text (longtext)',
                'integer' => 'Integer',
                'boolean' => 'Boolean',
                'date' => 'Date',
                'datetime' => 'DateTime',
                'json' => 'JSON',
                'float' => 'Float (decimal)',
            ])
            ->expectsQuestion("Is 'name' nullable?", false)
            ->expectsQuestion('Add another field?', false)
            ->assertSuccessful();

        $provider = Storage::disk('modules')->get(MAKE_MODULE.'/Providers/'.MAKE_MODULE.'ServiceProvider.php');
        expect($provider)->toContain('class '.MAKE_MODULE.'ServiceProvider extends ServiceProvider')
            ->not->toContain('configureRoutes');

        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Routes/V1.php');
    });
});

describe('migration', function () {
    it('replaces existing migration on force', function () {
        artisanCommand($this, 'make:module', [
            'name' => MAKE_MODULE,
            '--migration' => true,
        ])
            ->assertSuccessful();

        $migrationDir = MAKE_MODULE.'/Database/Migrations';
        expect(Storage::disk('modules')->files($migrationDir))->toHaveCount(1);

        artisanCommand($this, 'make:module', [
            'name' => MAKE_MODULE,
            '--migration' => true,
            '--force' => true,
        ])
            ->assertSuccessful();

        expect(Storage::disk('modules')->files($migrationDir))->toHaveCount(1);
    });
});

describe('route file', function () {
    it('generates route file with correct prefixes', function () {
        artisanCommand($this, 'make:module', ['name' => MAKE_MODULE])
            ->expectsQuestion('API version?', 'V1')
            ->expectsQuestion('Include timestamps?', true)
            ->expectsQuestion('Include soft deletes?', false)
            ->expectsChoice('Which operations to generate?', ['list', 'create', 'show', 'update', 'delete'], [
                'list' => 'List (index)',
                'create' => 'Create',
                'show' => 'Show',
                'update' => 'Update',
                'delete' => 'Delete',
                'bulk-delete' => 'Bulk Delete',
                'bulk-restore' => 'Bulk Restore',
            ])
            ->expectsChoice(
                'Which components would you like to create?',
                ['action', 'filter'],
                ['action' => 'CRUD Actions & Payloads', 'filter' => 'Query Filter', 'migration' => 'Migration', 'factory' => 'Factory', 'seeder' => 'Seeder', 'event' => 'Event'],
            )
            ->expectsChoice('Generate test files?', 'none', [
                'none' => 'None',
                'unit' => 'Unit Test',
                'feature' => 'Feature Test',
                'all' => 'Both',
            ])
            ->expectsQuestion('Field name?', 'name')
            ->expectsChoice("Field type for 'name'?", 'string', [
                'string' => 'String (varchar)',
                'text' => 'Text (longtext)',
                'integer' => 'Integer',
                'boolean' => 'Boolean',
                'date' => 'Date',
                'datetime' => 'DateTime',
                'json' => 'JSON',
                'float' => 'Float (decimal)',
            ])
            ->expectsQuestion("Is 'name' nullable?", false)
            ->expectsQuestion('Add another field?', false)
            ->assertSuccessful();

        $content = Storage::disk('modules')->get(MAKE_MODULE.'/Routes/V1.php');

        $slug = Str::kebab(Str::plural(MAKE_MODULE));
        expect($content)->toContain("prefix('{$slug}')")
            ->toContain("name('{$slug}.')");
    });
});

describe('test generation', function () {
    it('--tests=unit generates only unit test file', function () {
        artisanCommand($this, 'make:module', [
            'name' => MAKE_MODULE,
            '--except' => 'action,filter,migration,factory,seeder,event',
            '--tests' => 'unit',
        ])->assertSuccessful();

        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Tests/Unit/'.MAKE_MODULE.'UnitTest.php');
        Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Tests/Feature');
    });

    it('--tests=feature generates only feature test file', function () {
        artisanCommand($this, 'make:module', [
            'name' => MAKE_MODULE,
            '--except' => 'action,filter,migration,factory,seeder,event',
            '--tests' => 'feature',
        ])->assertSuccessful();

        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Tests/Feature/V1/'.MAKE_MODULE.'Test.php');
        Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Tests/Unit');
    });

    it('--tests=all generates both test files', function () {
        artisanCommand($this, 'make:module', [
            'name' => MAKE_MODULE,
            '--except' => 'action,filter,migration,factory,seeder,event',
            '--tests' => 'all',
        ])->assertSuccessful();

        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Tests/Feature/V1/'.MAKE_MODULE.'Test.php');
        Storage::disk('modules')->assertExists(MAKE_MODULE.'/Tests/Unit/'.MAKE_MODULE.'UnitTest.php');
    });
});
