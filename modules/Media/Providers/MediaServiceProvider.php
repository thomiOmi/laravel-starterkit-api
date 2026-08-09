<?php

declare(strict_types=1);

namespace Modules\Media\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Media\Models\Media;
use Modules\Media\Policies\MediaPolicy;

class MediaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configurePolicies();
    }

    public function register(): void {}

    /**
     * Register module policies explicitly.
     *
     * Laravel's policy auto-discovery only covers App\Models, so module
     * models require an explicit mapping.
     */
    protected function configurePolicies(): void
    {
        Gate::policy(Media::class, MediaPolicy::class);
    }
}
