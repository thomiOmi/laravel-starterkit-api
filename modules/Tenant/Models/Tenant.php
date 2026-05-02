<?php

declare(strict_types=1);

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Subscription\Models\Subscription;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * @property string $id
 * @property array $data
 * @property int|null $rate_limit
 * @property-read Subscription|null $subscription
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'data',
        'rate_limit',
    ];

    /**
     * Get the custom columns that are not in the data JSON column.
     *
     * @return array<int, string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'rate_limit',
        ];
    }

    /**
     * Get the tenant's current subscription.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'tenant_id')->where('status', 'active')->latestOfMany();
    }

    /**
     * Check if the tenant has a specific feature enabled in their plan.
     */
    public function hasFeature(string $feature): bool
    {
        $subscription = $this->subscription;

        if (! $subscription || ! $subscription->isActive()) {
            return false;
        }

        $features = $subscription->plan->features ?? [];

        return (bool) ($features[$feature] ?? false);
    }
}
