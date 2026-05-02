<?php

declare(strict_types=1);

namespace Modules\Subscription\Repositories;

use App\Repositories\BaseRepository;
use Modules\Subscription\Models\Subscription;

/**
 * @extends BaseRepository<Subscription>
 */
class SubscriptionRepository extends BaseRepository
{
    public function __construct(Subscription $model)
    {
        parent::__construct($model);
    }

    public function model(): string
    {
        return Subscription::class;
    }

    /**
     * Cancel active subscriptions for a tenant.
     */
    public function cancelActiveSubscriptions(string $tenantId): void
    {
        $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
    }
}
