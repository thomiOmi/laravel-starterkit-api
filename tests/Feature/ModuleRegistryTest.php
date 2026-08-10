<?php

declare(strict_types=1);

use App\Providers\ModuleServiceProvider;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Support\Facades\Facade;

covers([ModuleServiceProvider::class, RouteServiceProvider::class]);

describe('ModuleRegistry', function (): void {
    it('enables the shipped modules by default', function (): void {
        expect(config('modules.enabled'))->toBe(['iam', 'media']);
    });

    it('registers providers only for enabled modules', function (): void {
        $isolated = new Application(base_path());
        $isolated->bootstrapWith([LoadConfiguration::class]);
        $isolated['config']->set('modules.enabled', ['iam']);

        try {
            new ModuleServiceProvider($isolated)->register();

            expect($isolated->getProvider(\Modules\IAM\Providers\IAMServiceProvider::class))->not->toBeNull()
                ->and($isolated->getProvider(\Modules\Media\Providers\MediaServiceProvider::class))->toBeNull();
        } finally {
            Application::setInstance($this->app);
        }
    });

    it('loads routes only for enabled modules', function (): void {
        $isolated = Application::configure(base_path())->create();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($isolated);
        $isolated->bootstrapWith([
            LoadEnvironmentVariables::class,
            LoadConfiguration::class,
            RegisterFacades::class,
        ]);
        $isolated['config']->set('modules.enabled', ['iam']);
        $isolated->bootstrapWith([RegisterProviders::class, BootProviders::class]);

        try {
            expect($isolated['router']->has('v1.iam.auth.login'))->toBeTrue()
                ->and($isolated['router']->has('v1.media.media.index'))->toBeFalse();
        } finally {
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($this->app);
            Application::setInstance($this->app);
        }
    });
});
