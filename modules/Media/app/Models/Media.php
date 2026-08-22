<?php

declare(strict_types=1);

namespace Modules\Media\Models;

use App\Concerns\HasDefaultBehavior;
use App\Enums\MediaVisibilityEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property-read User|null $uploadedBy The user who uploaded the media.
 */
#[Fillable(['collection_name', 'disk', 'mime_type', 'size', 'path', 'visibility', 'meta', 'uploaded_by'])]
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
        ];
    }
}
