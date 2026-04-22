<?php

declare(strict_types=1);

namespace Modules\Blog\Models;

use App\Traits\Models\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

#[Fillable(['title', 'content', 'user_id'])]
class Blog extends Model
{
    use HasDefaultBehavior, HasFactory;

    /**
     * Get the user that owns the blog.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
