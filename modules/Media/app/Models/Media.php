<?php

declare(strict_types=1);

namespace Modules\Media\Models;

use App\Concerns\HasDefaultBehavior;
use App\Enums\MediaVisibilityEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\IAM\Models\User;
use Modules\Media\Builders\MediaBuilder;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Policies\MediaPolicy;

/**
 * @property string $id The unique identifier for the media item.
 * @property string $collection_name The logical collection the media belongs to.
 * @property string $disk The storage disk the file is stored on.
 * @property string $mime_type The MIME type of the stored file.
 * @property int $size The file size in bytes.
 * @property string $path The unique storage path of the file.
 * @property MediaVisibilityEnum $visibility Who may access the media.
 * @property array<string, mixed>|null $meta Free-form metadata (original name, dimensions, etc.).
 * @property string|null $uploaded_by The ULID of the owning user.
 * @property string|null $model_type The polymorphic model type.
 * @property string|null $model_id The polymorphic model identifier.
 * @property int|null $order_column The sort order within the collection.
 * @property-read User|null $uploadedBy The user who uploaded the media.
 * @property-read Model|Model|null $model The owning model.
 */
#[Fillable(['collection_name', 'disk', 'mime_type', 'size', 'path', 'visibility', 'meta', 'uploaded_by', 'model_type', 'model_id', 'order_column'])]
#[UseEloquentBuilder(MediaBuilder::class)]
#[UseFactory(MediaFactory::class)]
#[UsePolicy(MediaPolicy::class)]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasDefaultBehavior, HasFactory;

    /**
     * Get the user who uploaded the media.
     *
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the owning model of the media.
     *
     * @return MorphTo<Model, $this>
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to order by the collection order.
     *
     * @param  Builder<Media>  $query
     * @return Builder<Media>
     */
    protected function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_column');
    }

    /**
     * Determine whether the media belongs to the given key.
     */
    public function isOwnedBy(string|int|null $userId): bool
    {
        return $userId !== null && $this->uploaded_by === (string) $userId;
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
            'visibility' => MediaVisibilityEnum::class,
            'meta' => 'array',
            'order_column' => 'integer',
        ];
    }
}
