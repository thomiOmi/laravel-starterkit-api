<?php

declare(strict_types=1);

namespace Modules\Media\Models;

use App\Concerns\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\IAM\Models\User;
use Modules\Media\Builders\MediaBuilder;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Policies\MediaPolicy;

/**
 * @property string $id The unique identifier (ULID).
 * @property string $disk The storage disk where the file lives.
 * @property string $mime_type The MIME type of the file.
 * @property int $size The file size in bytes.
 * @property string $path The storage path of the file.
 * @property array<string, mixed>|null $meta The file metadata (original name, extension).
 * @property string|null $uploaded_by The ID of the user who uploaded the file.
 * @property-read User|null $uploadedBy The user who uploaded the file.
 * @property Carbon|null $created_at The timestamp when created.
 * @property Carbon|null $updated_at The timestamp when updated.
 */
#[Fillable(['disk', 'mime_type', 'size', 'path', 'meta', 'uploaded_by'])]
#[Hidden(['path'])]
#[UseFactory(MediaFactory::class)]
#[UseEloquentBuilder(MediaBuilder::class)]
#[UsePolicy(MediaPolicy::class)]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasDefaultBehavior, HasFactory;

    /**
     * Get the user who uploaded this media.
     *
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
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
            'meta' => 'array',
        ];
    }
}
