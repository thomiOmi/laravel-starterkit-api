<?php

declare(strict_types=1);

namespace Modules\Media\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Media\Models\Media;
use Modules\Media\Models\Observers\MediaObserver;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Media::observe(MediaObserver::class);
    }
}
