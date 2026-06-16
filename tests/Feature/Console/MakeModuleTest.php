<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $modulePath = base_path('modules/TestModule');

    if (File::exists($modulePath)) {
        File::deleteDirectory($modulePath);
    }
});

afterEach(function () {
    $modulePath = base_path('modules/TestModule');
    if (File::exists($modulePath)) {
        File::deleteDirectory($modulePath);
    }
});

it('can create a new module interactively', function () {
    $this->artisan('make:module TestModule')
        ->expectsConfirmation('Create Concrete Repository?', 'yes')
        ->expectsConfirmation('Create CRUD Actions & Payloads?', 'yes')
        ->expectsConfirmation('Create Query Filter?', 'yes')
        ->expectsConfirmation('Create Migration?', 'yes')
        ->expectsConfirmation('Create Factory?', 'yes')
        ->expectsConfirmation('Create Seeder?', 'yes')
        ->expectsConfirmation('Create Event?', 'yes')
        ->assertExitCode(0);

    $modulePath = base_path('modules/TestModule');
    expect(File::exists($modulePath))->toBeTrue()
        ->and(File::exists($modulePath.'/Models/TestModule.php'))->toBeTrue()
        ->and(File::exists($modulePath.'/Resources/TestModuleResource.php'))->toBeTrue()
        ->and(File::exists($modulePath.'/Controllers/V1/IndexController.php'))->toBeTrue()
        ->and(File::exists($modulePath.'/Repositories/TestModuleRepository.php'))->toBeTrue()
        ->and(File::exists($modulePath.'/Routes/V1.php'))->toBeTrue()
        ->and(File::exists($modulePath.'/Events/TestModuleCreated.php'))->toBeTrue();
});

it('can create a new module with custom api version', function () {
    $this->artisan('make:module TestModule --api-version=V2')
        ->expectsConfirmation('Create Concrete Repository?', 'yes')
        ->expectsConfirmation('Create CRUD Actions & Payloads?', 'yes')
        ->expectsConfirmation('Create Query Filter?', 'yes')
        ->expectsConfirmation('Create Migration?', 'yes')
        ->expectsConfirmation('Create Factory?', 'yes')
        ->expectsConfirmation('Create Seeder?', 'yes')
        ->expectsConfirmation('Create Event?', 'yes')
        ->assertExitCode(0);

    $modulePath = base_path('modules/TestModule');
    expect(File::exists($modulePath))->toBeTrue()
        ->and(File::exists($modulePath.'/Controllers/V2/IndexController.php'))->toBeTrue()
        ->and(File::exists($modulePath.'/Routes/V2.php'))->toBeTrue()
        ->and(File::exists($modulePath.'/Payloads/V2/CreateTestModulePayload.php'))->toBeTrue();
});

it('can skip optional components', function () {
    $this->artisan('make:module TestModule')
        ->expectsConfirmation('Create Concrete Repository?', 'no')
        ->expectsConfirmation('Create CRUD Actions & Payloads?', 'no')
        ->expectsConfirmation('Create Query Filter?', 'no')
        ->expectsConfirmation('Create Migration?', 'no')
        ->expectsConfirmation('Create Factory?', 'no')
        ->expectsConfirmation('Create Seeder?', 'no')
        ->expectsConfirmation('Create Event?', 'no')
        ->assertExitCode(0);

    $modulePath = base_path('modules/TestModule');
    expect(File::exists($modulePath.'/Actions/CreateTestModuleAction.php'))->toBeFalse()
        ->and(File::exists($modulePath.'/Repositories/TestModuleRepository.php'))->toBeFalse()
        ->and(File::exists($modulePath.'/Payloads/V1/CreateTestModulePayload.php'))->toBeFalse();
});

it('asks for overwrite if module exists', function () {
    $modulePath = base_path('modules/TestModule');
    File::makeDirectory($modulePath, 0755, true);

    $this->artisan('make:module TestModule')
        ->expectsConfirmation('Module TestModule already exists. Do you want to overwrite it?', 'no')
        ->expectsOutput('Aborted.')
        ->assertExitCode(0);
});

it('can create module non-interactively with flags', function () {
    $this->artisan('make:module TestModule --no-interaction --repository --action --filter --migration --factory --seeder --event')
        ->assertExitCode(0);

    $modulePath = base_path('modules/TestModule');
    expect(File::exists($modulePath.'/Actions/CreateTestModuleAction.php'))->toBeTrue()
        ->and(File::exists($modulePath.'/Repositories/TestModuleRepository.php'))->toBeTrue()
        ->and(File::exists($modulePath.'/Routes/V1.php'))->toBeTrue()
        ->and(File::exists($modulePath.'/Events/TestModuleCreated.php'))->toBeTrue();
});

it('can skip components with --except flag', function () {
    $this->artisan('make:module TestModule --no-interaction --except=repository,action,filter,migration,factory,seeder,event')
        ->assertExitCode(0);

    $modulePath = base_path('modules/TestModule');
    expect(File::exists($modulePath.'/Repositories'))->toBeFalse()
        ->and(File::exists($modulePath.'/Actions'))->toBeFalse();
});
