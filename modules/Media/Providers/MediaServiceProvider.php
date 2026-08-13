<?php

declare(strict_types=1);

namespace Modules\Media\Providers;

use App\Providers\ModuleServiceProvider;

/**
 * Wires the Media module into the framework.
 *
 * Declaration-only provider: the base class handles config merge, build-time
 * features, migrations, routes, and translations. Media has no module-specific
 * middleware aliases or bindings today.
 */
class MediaServiceProvider extends ModuleServiceProvider
{
    /**
     * The TitleCase module folder name.
     */
    protected function moduleName(): string
    {
        return 'Media';
    }
}
