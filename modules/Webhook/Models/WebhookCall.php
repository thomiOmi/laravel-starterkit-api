<?php

declare(strict_types=1);

namespace Modules\Webhook\Models;

use App\Traits\Models\HasDefaultBehavior;
use App\Traits\Models\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $webhook_id
 * @property string $event
 * @property array $payload
 * @property int|null $status_code
 * @property string|null $response_body
 * @property string $status
 * @property int $tries
 * @property Carbon|null $last_attempt_at
 */
class WebhookCall extends Model
{
    use HasDefaultBehavior, HasTenant;

    protected $fillable = [
        'webhook_id',
        'event',
        'payload',
        'status_code',
        'response_body',
        'status',
        'tries',
        'last_attempt_at',
        'tenant_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'last_attempt_at' => 'datetime',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}
