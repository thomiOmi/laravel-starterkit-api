<?php

declare(strict_types=1);

namespace Modules\Media\Models;

use App\Concerns\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Media\Database\Factories\MediaConversionFactory;

/**
 * @property string $id
 * @property string $media_id
 * @property string $name
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int|null $size
 * @property string|null $etag
 */
#[Fillable(['media_id', 'name', 'disk', 'path', 'mime_type', 'size', 'etag'])]
#[UseFactory(MediaConversionFactory::class)]
class MediaConversion extends Model
{
    /** @use HasFactory<MediaConversionFactory> */
    use HasDefaultBehavior, HasFactory;

    /**
     * Get the media that owns the conversion.
     *
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }
}
