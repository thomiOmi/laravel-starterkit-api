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
        ->expectsConfirmation('Create Repository?', 'yes')
        ->expectsConfirmation('Create CRUD Actions?', 'yes')
        ->expectsConfirmation('Create DTO?', 'yes')
        ->expectsConfirmation('Create Form Request?', 'yes')
        ->expectsConfirmation('Create Query Filter?', 'yes')
        ->expectsConfirmation('Create Migration?', 'yes')
        ->expectsConfirmation('Create Factory?', 'yes')
        ->expectsConfirmation('Create Seeder?', 'yes')
        ->expectsConfirmation('Create Resource?', 'yes')
        ->assertExitCode(0);

    $modulePath = base_path('modules/TestModule');
    expect(File::exists($modulePath))->toBeTrue()
        ->and(File::exists($modulePath.'/Models/TestModule.php'))->toBeTrue()
        ->and(File::exists($modulePath.'/Repositories/TestModuleRepository.php'))->toBeTrue()
        ->and(File::exists($modulePath.'/Routes/V1.php'))->toBeTrue();
});

it('can skip optional components', function () {
    $this->artisan('make:module TestModule')
        ->expectsConfirmation('Create Repository?', 'no')
        ->expectsConfirmation('Create CRUD Actions?', 'no')
        ->expectsConfirmation('Create DTO?', 'no')
        ->expectsConfirmation('Create Form Request?', 'no')
        ->expectsConfirmation('Create Query Filter?', 'no')
        ->expectsConfirmation('Create Migration?', 'no')
        ->expectsConfirmation('Create Factory?', 'no')
        ->expectsConfirmation('Create Seeder?', 'no')
        ->expectsConfirmation('Create Resource?', 'no')
        ->assertExitCode(0);

    $modulePath = base_path('modules/TestModule');
    expect(File::exists($modulePath.'/Repositories/TestModuleRepository.php'))->toBeFalse()
        ->and(File::exists($modulePath.'/DTOs/TestModuleDTO.php'))->toBeFalse();
});

it('asks for overwrite if module exists', function () {
    $modulePath = base_path('modules/TestModule');
    File::makeDirectory($modulePath, 0755, true);

    $this->artisan('make:module TestModule')
        ->expectsConfirmation('Module TestModule already exists. Do you want to overwrite it?', 'no')
        ->expectsOutput('Aborted.')
        ->assertExitCode(0);
});
