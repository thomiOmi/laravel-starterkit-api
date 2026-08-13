<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Support\Facades\Facade;
use Modules\Organization\Providers\OrganizationServiceProvider;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;

covers(OrganizationServiceProvider::class);

describe('OrganizationModule', function (): void {
    it('is not registered by default', function (): void {
        expect(config('modules.modules.organization.active', false))->toBeFalse()
            ->and(config('tenancy'))->toBeNull()
            ->and($this->app->getProvider(OrganizationServiceProvider::class))->toBeNull();
    });

    it('registers tenancy when the module is active', function (): void {
        $isolated = Application::configure(base_path())->create();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($isolated);
        $isolated->bootstrapWith([
            LoadEnvironmentVariables::class,
            LoadConfiguration::class,
            RegisterFacades::class,
        ]);
        $isolated['config']->set('modules.modules', [
            'iam' => ['active' => true, 'features' => []],
            'media' => ['active' => true, 'features' => []],
            'organization' => ['active' => true, 'features' => ['multi-tenancy' => true]],
        ]);
        $isolated->bootstrapWith([RegisterProviders::class, BootProviders::class]);

        try {
            expect($isolated->getProvider(OrganizationServiceProvider::class))->not->toBeNull()
                ->and($isolated['config']->get('tenancy.routes'))->toBeFalse()
                ->and($isolated['config']->get('tenancy.bootstrappers'))->not->toContain(DatabaseTenancyBootstrapper::class)
                ->and($isolated['config']->get('tenancy.migration_parameters.--path'))
                ->toContain(realpath(__DIR__.'/../../modules/Organization/Database/Migrations/tenant'));
        } finally {
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($this->app);
            Application::setInstance($this->app);
        }
    });
});
