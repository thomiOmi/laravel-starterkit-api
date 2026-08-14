<?php

declare(strict_types=1);

use App\Console\Commands\ModuleListCommand;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

covers(ModuleListCommand::class);

beforeEach(function (): void {
    Storage::fake('modules');
});

/**
 * @return list<string>
 */
function moduleTableHeaders(): array
{
    return ['Module Name', 'Status', 'Controllers', 'Actions', 'Payloads', 'Builders', 'Migrations', 'Routes'];
}

function putModuleFile(string $relativePath, string $content = '<?php'): void
{
    Storage::disk('modules')->put($relativePath, $content);
}

describe('module:list command', function () {
    describe('empty modules directory', function () {
        it('shows "No modules found" message', function (): void {
            artisanCommand($this, 'module:list')
                ->assertSuccessful()
                ->expectsOutputToContain('No modules found.');
        });
    });

    describe('module table output', function () {
        it('lists a fully populated module with correct counts', function (): void {
            putModuleFile('Billing/Providers/BillingServiceProvider.php');
            putModuleFile('Billing/Routes/api.php');
            putModuleFile('Billing/Controllers/InvoiceController.php');
            putModuleFile('Billing/Controllers/Nested/RefundController.php');
            putModuleFile('Billing/Actions/CreateInvoiceAction.php');
            putModuleFile('Billing/Actions/Nested/ShouldNotCount.php');
            putModuleFile('Billing/Payloads/InvoicePayload.php');
            putModuleFile('Billing/Payloads/Nested/DeepPayload.php');
            putModuleFile('Billing/Builders/StatusBuilder.php');
            putModuleFile('Billing/Database/Migrations/2026_01_01_create_invoices_table.php');

            artisanCommand($this, 'module:list')
                ->expectsPromptsTable(
                    moduleTableHeaders(),
                    [['Billing', 'Active', '2', '1', '2', '1', '1', 'Yes']],
                )
                ->assertSuccessful();
        });

        it('marks a module without a ServiceProvider as inactive', function (): void {
            putModuleFile('Draft/Controllers/DraftController.php');

            artisanCommand($this, 'module:list')
                ->expectsPromptsTable(
                    moduleTableHeaders(),
                    [['Draft', 'Inactive', '1', '0', '0', '0', '0', 'No']],
                )
                ->assertSuccessful();
        });

        it('detects routes via V1.php or api.php independently', function (): void {
            putModuleFile('Alpha/Providers/AlphaServiceProvider.php');
            putModuleFile('Alpha/Routes/V1.php');

            putModuleFile('Beta/Providers/BetaServiceProvider.php');
            putModuleFile('Beta/Routes/api.php');

            putModuleFile('Gamma/Providers/GammaServiceProvider.php');

            artisanCommand($this, 'module:list')
                ->expectsPromptsTable(
                    moduleTableHeaders(),
                    [
                        ['Alpha', 'Active', '0', '0', '0', '0', '0', 'Yes'],
                        ['Beta', 'Active', '0', '0', '0', '0', '0', 'Yes'],
                        ['Gamma', 'Active', '0', '0', '0', '0', '0', 'No'],
                    ],
                )
                ->assertSuccessful();
        });
    });

    describe('file counting', function () {
        it('does not count nested files for non-recursive directories', function (): void {
            // Actions and Builders are configured as non-recursive.
            putModuleFile('Reporting/Providers/ReportingServiceProvider.php');
            putModuleFile('Reporting/Actions/TopLevelAction.php');
            putModuleFile('Reporting/Actions/Sub/NestedAction.php'); // must be ignored
            putModuleFile('Reporting/Builders/TopLevelBuilder.php');
            putModuleFile('Reporting/Builders/Sub/NestedBuilder.php'); // must be ignored

            // Controllers and Payloads are recursive — nested files must count.
            putModuleFile('Reporting/Controllers/TopController.php');
            putModuleFile('Reporting/Controllers/Sub/NestedController.php');

            artisanCommand($this, 'module:list')
                ->expectsPromptsTable(
                    moduleTableHeaders(),
                    [['Reporting', 'Active', '2', '1', '0', '1', '0', 'No']],
                )
                ->assertSuccessful();
        });
    });

    describe('module name prefix handling', function () {
        it('does not let a prefix-sharing module name bleed into another module\'s counts', function (): void {
            // Regression guard for the "User" vs "UserProfile" prefix concern.
            putModuleFile('User/Providers/UserServiceProvider.php');
            putModuleFile('User/Controllers/UserController.php');

            putModuleFile('UserProfile/Providers/UserProfileServiceProvider.php');
            putModuleFile('UserProfile/Controllers/ProfileController.php');
            putModuleFile('UserProfile/Controllers/Nested/AvatarController.php');

            artisanCommand($this, 'module:list')
                ->expectsPromptsTable(
                    moduleTableHeaders(),
                    [
                        ['User', 'Active', '1', '0', '0', '0', '0', 'No'],
                        ['UserProfile', 'Active', '2', '0', '0', '0', '0', 'No'],
                    ],
                )
                ->assertSuccessful();
        });

        it('does not leak file counts between two modules in the same run', function (): void {
            putModuleFile('AlphaModule/Providers/AlphaModuleServiceProvider.php');
            putModuleFile('AlphaModule/Controllers/UserController.php');

            putModuleFile('AlphaModuleExtended/Providers/AlphaModuleExtendedServiceProvider.php');
            putModuleFile('AlphaModuleExtended/Controllers/One.php');
            putModuleFile('AlphaModuleExtended/Controllers/Two.php');

            artisanCommand($this, 'module:list')
                ->expectsPromptsTable(
                    moduleTableHeaders(),
                    [
                        ['AlphaModule', 'Active', '1', '0', '0', '0', '0', 'No'],
                        ['AlphaModuleExtended', 'Active', '2', '0', '0', '0', '0', 'No'],
                    ],
                )
                ->assertSuccessful();
        });
    });

    describe('error handling', function () {
        it('shows an error when the modules disk throws', function (): void {
            Storage::shouldReceive('disk')
                ->with('modules')
                ->andReturn(tap(Mockery::mock(Filesystem::class), function ($mock) {
                    $mock->shouldReceive('directories')->once()->andThrow(new RuntimeException('disk unreachable'));
                }));

            artisanCommand($this, 'module:list')
                ->assertSuccessful()
                ->expectsOutputToContain('Modules directory not accessible: disk unreachable');
        });
    });
});
