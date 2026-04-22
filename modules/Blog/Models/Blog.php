<?php

declare(strict_types=1);

namespace Modules\Blog\Models;

use App\Traits\Models\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\User\Models\User;

#[Fillable(['title', 'content', 'user_id'])]
/**
 * @property string $id
 * @property string $title
 * @property string $content
 * @property string $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 */
class Blog extends Model
{
    use HasDefaultBehavior, HasFactory;

    /**
     * Get the user that owns the blog.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
