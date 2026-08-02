<?php

declare(strict_types=1);

use App\Console\Commands\ModuleListCommand;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

covers(ModuleListCommand::class);

beforeEach(function () {
    Storage::fake('modules');
});

describe('module:list command', function () {
    it('lists modules with headers, status, and file counts', function () {
        Storage::disk('modules')->put('AlphaModule/Providers/AlphaModuleServiceProvider.php', '<?php');
        Storage::disk('modules')->put('AlphaModule/Controllers/UserController.php', '<?php');
        Storage::disk('modules')->put('AlphaModule/Controllers/Admin/UserController.php', '<?php');
        Storage::disk('modules')->put('AlphaModule/Actions/ListUsers.php', '<?php');
        Storage::disk('modules')->put('AlphaModule/Payloads/UserPayload.php', '<?php');
        Storage::disk('modules')->put('AlphaModule/Payloads/Users/ListPayload.php', '<?php');
        Storage::disk('modules')->put('AlphaModule/Filters/UserFilter.php', '<?php');
        Storage::disk('modules')->put('AlphaModule/Database/Migrations/2026_01_01_000000_create_users_table.php', '<?php');
        Storage::disk('modules')->put('AlphaModule/Routes/V1.php', '<?php');

        artisanCommand($this, 'module:list')
            ->expectsPromptsTable(
                ['Module Name', 'Status', 'Ctlr', 'Actn', 'Pld', 'Flt', 'Migr', 'Rte'],
                [['AlphaModule', 'Active', '2', '1', '2', '1', '1', 'Yes']],
            )
            ->assertSuccessful();
    });

    it('marks a module without a service provider as Inactive', function () {
        Storage::disk('modules')->put('BetaModule/Actions/ListUsers.php', '<?php');

        artisanCommand($this, 'module:list')
            ->expectsPromptsTable(
                ['Module Name', 'Status', 'Ctlr', 'Actn', 'Pld', 'Flt', 'Migr', 'Rte'],
                [['BetaModule', 'Inactive', '0', '1', '0', '0', '0', 'No']],
            )
            ->assertSuccessful();
    });

    it('shows No routes when a module has no route file', function () {
        Storage::disk('modules')->put('GammaModule/Providers/GammaModuleServiceProvider.php', '<?php');

        artisanCommand($this, 'module:list')
            ->expectsPromptsTable(
                ['Module Name', 'Status', 'Ctlr', 'Actn', 'Pld', 'Flt', 'Migr', 'Rte'],
                [['GammaModule', 'Active', '0', '0', '0', '0', '0', 'No']],
            )
            ->assertSuccessful();
    });

    it('handles an empty modules directory', function () {
        artisanCommand($this, 'module:list')
            ->expectsPromptsTable(
                ['Module Name', 'Status', 'Ctlr', 'Actn', 'Pld', 'Flt', 'Migr', 'Rte'],
                [],
            )
            ->assertSuccessful();
    });

    it('shows an error when the modules directory is missing', function () {
        Config::set('filesystems.disks.modules.root', storage_path('modules-does-not-exist'));
        Storage::forgetDisk('modules');

        artisanCommand($this, 'module:list')
            ->assertSuccessful()
            ->expectsOutputToContain('Modules directory not found.');
    });
});
