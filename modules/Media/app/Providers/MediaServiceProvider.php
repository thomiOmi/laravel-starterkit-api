<?php

declare(strict_types=1);

namespace Modules\Media\Providers;

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
     * @var class-string<\Illuminate\Console\Command>[]
     */
    protected array $commands = [];
}