<?php

declare(strict_types=1);

namespace Modules\Webhook\Models;

use App\Traits\Models\HasAuditLogs;
use App\Traits\Models\HasDefaultBehavior;
use App\Traits\Models\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string $url
 * @property string|null $secret
 * @property array $events
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Webhook extends Model
{
    use HasAuditLogs, HasDefaultBehavior, HasFactory, HasTenant;

    protected $fillable = [
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'tenant_id',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
    ];

    public function calls(): HasMany
    {
        return $this->hasMany(WebhookCall::class);
    }
}
