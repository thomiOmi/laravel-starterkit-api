<?php

declare(strict_types=1);

namespace Modules\Media\Providers;

use Illuminate\Console\Command;
use Modules\Media\Console\Commands\MediaCleanupCommand;
use Modules\Media\Console\Commands\MediaReprocessCommand;
use Modules\Media\Support\FileNamer\DefaultFileNamer;
use Modules\Media\Support\FileNamer\MediaFileNamer;
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

        $this->app->singleton(MediaUrlGenerator::class, function (): MediaUrlGenerator {
            $class = config()->string('media.url_generator', DefaultUrlGenerator::class);

            if (! is_a($class, MediaUrlGenerator::class, true)) {
                $class = DefaultUrlGenerator::class;
            }

            return new $class;
        });
        $this->app->singleton(MediaPathGenerator::class, function (): MediaPathGenerator {
            $class = config()->string('media.path_generator', DefaultPathGenerator::class);

            if (! is_a($class, MediaPathGenerator::class, true)) {
                $class = DefaultPathGenerator::class;
            }

            return new $class;
        });
        $this->app->singleton(MediaFileNamer::class, function (): MediaFileNamer {
            $class = config()->string('media.file_namer', DefaultFileNamer::class);

            if (! is_a($class, MediaFileNamer::class, true)) {
                $class = DefaultFileNamer::class;
            }

            return new $class;
        });
    }
}
