<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Media\Models\Media;

/**
 * Attach an existing media item to a different model.
 *
 * Authorization must be performed by the caller (FormRequest authorize()
 * or Controller via Gate::authorize) before invoking this action.
 */
final readonly class AttachMediaAction
{
    public function handle(Media $media, Model $newOwner): Media
    {
        $media->model()->associate($newOwner);
        $media->save();

        return $media;
    }
}
