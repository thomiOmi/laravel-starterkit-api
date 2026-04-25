<?php

declare(strict_types=1);

namespace Modules\ApiKey\Models;

use App\Traits\Models\HasAuditLogs;
use App\Traits\Models\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\ApiKey\Database\Factories\ApiKeyFactory;
use Modules\User\Models\User;

/**
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property string $key
 * @property string $secret_prefix
 * @property array|null $abilities
 * @property array|null $ip_whitelist
 * @property Carbon|null $last_used_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
class ApiKey extends Model
{
    use HasAuditLogs, HasDefaultBehavior, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'key',
        'secret_prefix',
        'abilities',
        'ip_whitelist',
        'last_used_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'abilities' => 'array',
        'ip_whitelist' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the API Key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ApiKeyFactory
    {
        return ApiKeyFactory::new();
    }
}
