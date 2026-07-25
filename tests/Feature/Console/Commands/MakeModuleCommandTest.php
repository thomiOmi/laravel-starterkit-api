<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

const MAKE_MODULE = 'TestMod';

beforeEach(function () {
    Storage::fake('modules');
});

test('can create a module with name argument', function () {
    $this->artisan('make:module', ['name' => MAKE_MODULE])
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
    Storage::disk('modules')->assertExists(MAKE_MODULE.'/Tests/Feature/V1/'.MAKE_MODULE.'Test.php');
    Storage::disk('modules')->assertExists(MAKE_MODULE.'/Tests/Unit/'.MAKE_MODULE.'ActionTest.php');
});

test('prompts for missing name', function () {
    $this->artisan('make:module')
        ->expectsQuestion('What is the name of the module?', MAKE_MODULE)
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
    Storage::disk('modules')->assertExists(MAKE_MODULE.'/Tests/Unit/'.MAKE_MODULE.'ActionTest.php');
});

test('aborts when module exists without force', function () {
    Storage::disk('modules')->makeDirectory(MAKE_MODULE);

    $this->artisan('make:module', ['name' => MAKE_MODULE])
        ->expectsQuestion('Module TestMod already exists. Do you want to overwrite it?', false)
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
        ->assertSuccessful();

    expect(Storage::disk('modules')->exists(MAKE_MODULE.'/Providers'))->toBeFalse();
});

test('creates module with force flag when exists', function () {
    Storage::disk('modules')->makeDirectory(MAKE_MODULE.'/Models');
    Storage::disk('modules')->put(MAKE_MODULE.'/Models/'.MAKE_MODULE.'.php', '<?php // old content');

    $this->artisan('make:module', [
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
    Storage::disk('modules')->assertExists(MAKE_MODULE.'/Tests/Unit/'.MAKE_MODULE.'ActionTest.php');
});

test('respects --except flag', function () {
    $this->artisan('make:module', [
        'name' => MAKE_MODULE,
        '--except' => 'action,filter,migration,factory,seeder,event',
    ])
        ->assertSuccessful();

    Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Actions');
    Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Filters');
    Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Database/Migrations');
    Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Database/Factories');
    Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Database/Seeders');
    Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Events');
    Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Tests/Unit');
});

test('creates optional components when flags are passed', function () {
    $this->artisan('make:module', [
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
    Storage::disk('modules')->assertExists(MAKE_MODULE.'/Events/'.MAKE_MODULE.'Created.php');
    Storage::disk('modules')->assertExists(MAKE_MODULE.'/Tests/Unit/'.MAKE_MODULE.'ActionTest.php');
});

test('uses api-version option correctly', function () {
    Config::set('apiroute.supported_versions', ['V1', 'V2']);

    $this->artisan('make:module', [
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
    expect($provider)->toContain('api/v2');
    Storage::disk('modules')->assertExists(MAKE_MODULE.'/Tests/Unit/'.MAKE_MODULE.'ActionTest.php');
});

test('warns about unknown --except components', function () {
    $this->artisan('make:module', [
        'name' => MAKE_MODULE,
        '--except' => 'unknown,action',
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Unknown --except components: unknown');

    Storage::disk('modules')->assertMissing(MAKE_MODULE.'/Actions');
});

test('rejects invalid api-version format', function () {
    $this->artisan('make:module', [
        'name' => 'VersionTest',
        '--except' => 'action,filter,migration,factory,seeder,event',
        '--api-version' => 'v_1',
    ])
        ->expectsOutputToContain('Unsupported API version: V_1');

    Storage::disk('modules')->assertMissing('VersionTest');
});

test('accepts lowercase api-version and uppercases it', function () {
    $this->artisan('make:module', [
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
    expect($provider)->toContain('api/v1');
});

test('replaces existing migration on force', function () {
    $this->artisan('make:module', [
        'name' => MAKE_MODULE,
        '--migration' => true,
    ])
        ->assertSuccessful();

    $migrationDir = MAKE_MODULE.'/Database/Migrations';
    expect(Storage::disk('modules')->files($migrationDir))->toHaveCount(1);

    $this->artisan('make:module', [
        'name' => MAKE_MODULE,
        '--migration' => true,
        '--force' => true,
    ])
        ->assertSuccessful();

    expect(Storage::disk('modules')->files($migrationDir))->toHaveCount(1);
});

test('generates route file with correct prefixes', function () {
    $this->artisan('make:module', ['name' => MAKE_MODULE])
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
    expect($content)->toContain("prefix('{$slug}')");
    expect($content)->toContain("name('{$slug}.')");
    Storage::disk('modules')->assertExists(MAKE_MODULE.'/Tests/Unit/'.MAKE_MODULE.'ActionTest.php');
});
