<?php

declare(strict_types=1);

use App\Providers\ModuleLoaderServiceProvider;
use App\Providers\ModuleServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Support\Facades\Facade;
use Modules\IAM\Providers\IAMServiceProvider;
use Modules\Media\Providers\MediaServiceProvider;

covers([ModuleServiceProvider::class, ModuleLoaderServiceProvider::class]);

describe('ModuleRegistry', function (): void {
    it('marks iam and media active and organization inactive by default', function (): void {
        expect(config('modules.modules.iam.active', false))->toBeTrue()
            ->and(config('modules.modules.media.active', false))->toBeTrue()
            ->and(config('modules.modules.organization.active', false))->toBeFalse();
    });

    it('registers providers only for active modules', function (): void {
        $isolated = new Application(base_path());
        $isolated->bootstrapWith([LoadConfiguration::class]);
        $isolated['config']->set('modules.modules', [
            'iam' => ['active' => true, 'features' => []],
            'media' => ['active' => false, 'features' => []],
        ]);

        try {
            new ModuleLoaderServiceProvider($isolated)->register();

            expect($isolated->getProvider(IAMServiceProvider::class))->toBeInstanceOf(IAMServiceProvider::class)
                ->and($isolated->getProvider(MediaServiceProvider::class))->toBeNull();
        } finally {
            Application::setInstance($this->app);
        }
    });

    it('keeps an inactive module provider inert even when registered directly', function (): void {
        $isolated = new Application(base_path());
        $isolated->bootstrapWith([LoadConfiguration::class]);
        $isolated['config']->set('modules.modules', [
            'media' => ['active' => false, 'features' => []],
        ]);

        try {
            $provider = new MediaServiceProvider($isolated);
            $provider->register();

            expect($isolated['config']->get('media.features'))->toBeNull();
        } finally {
            Application::setInstance($this->app);
        }
    });

    it('loads routes only for active modules', function (): void {
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
            'media' => ['active' => false, 'features' => []],
        ]);
        $isolated->bootstrapWith([RegisterProviders::class, BootProviders::class]);

        try {
            $routes = $isolated->make('router')->getRoutes();

            expect($routes->hasNamedRoute('v1.iam.auth.login'))->toBeTrue()
                ->and($routes->hasNamedRoute('v1.media.media.index'))->toBeFalse();
        } finally {
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($this->app);
            Application::setInstance($this->app);
        }
    });
});
