<?php

namespace Modules\IAM\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class IAMServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'IAM';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'iam';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
