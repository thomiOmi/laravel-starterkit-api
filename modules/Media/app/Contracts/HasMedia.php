<?php

declare(strict_types=1);

namespace Modules\Media\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Media\Models\Media;

/**
 * Contract for models that own media.
 *
 * @mixin Model
 *
 * @phpstan-require-extends Model
 */
interface HasMedia
{
    /**
     * Get the media owned by the model.
     *
     * @return MorphMany<Media, Model>
     */
    public function media(): MorphMany;
}
