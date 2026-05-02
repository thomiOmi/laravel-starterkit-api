<?php

declare(strict_types=1);

namespace Modules\Media\Models\Observers;

use Modules\Media\Models\Media;

class MediaObserver
{
    /**
     * Handle the Media "creating" event.
     */
    public function creating(Media $media): void
    {
        if (tenant('id') && ! $media->tenant_id) {
            $media->tenant_id = tenant('id');
        }
    }
}
