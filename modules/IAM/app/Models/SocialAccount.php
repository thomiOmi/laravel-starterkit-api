<?php

declare(strict_types=1);

namespace Modules\IAM\Models;

use App\Concerns\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id The unique identifier (ULID).
 * @property string $user_id The ID of the user the account belongs to.
 * @property string $provider The social auth provider.
 * @property string $provider_id The social auth provider ID.
 * @property string|null $avatar The avatar URL provided by the provider.
 * @property Carbon|null $created_at The timestamp when created.
 * @property Carbon|null $updated_at The timestamp when updated.
 */
#[Fillable(['user_id', 'provider', 'provider_id', 'avatar'])]
class SocialAccount extends Model
{
    use HasDefaultBehavior;

    /**
     * Get the user this social account belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
