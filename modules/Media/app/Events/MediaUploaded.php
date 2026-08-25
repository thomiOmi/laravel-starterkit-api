<?php

declare(strict_types=1);

namespace Modules\Media\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Media\Models\Media;

final readonly class MediaUploaded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Media $media,
    ) {}
}
