<?php

declare(strict_types=1);

namespace Modules\Media\Providers;

use Illuminate\Console\Command;
use Modules\Media\Console\Commands\MediaCleanupCommand;
use Modules\Media\Console\Commands\MediaReprocessCommand;
use Modules\Media\Support\PathGenerator\DefaultPathGenerator;
use Modules\Media\Support\PathGenerator\MediaPathGenerator;
use Modules\Media\Support\UrlGenerator\DefaultUrlGenerator;
use Modules\Media\Support\UrlGenerator\MediaUrlGenerator;
use Nwidart\Modules\Support\ModuleServiceProvider;

class MediaServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Media';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'media';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Artisan command classes to register (add module commands here).
     *
     * @var class-string<Command>[]
     */
    protected array $commands = [
        MediaCleanupCommand::class,
        MediaReprocessCommand::class,
    ];

    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        parent::register();

        $this->app->singleton(MediaUrlGenerator::class, DefaultUrlGenerator::class);
        $this->app->singleton(MediaPathGenerator::class, DefaultPathGenerator::class);
    }
}
