<?php

declare(strict_types=1);

namespace Modules\Subscription\Repositories;

use App\Repositories\BaseRepository;
use Modules\Subscription\Models\SubscriptionPlan;

/**
 * @extends BaseRepository<SubscriptionPlan>
 */
class SubscriptionPlanRepository extends BaseRepository
{
    public function __construct(SubscriptionPlan $model)
    {
        parent::__construct($model);
    }

    public function model(): string
    {
        return SubscriptionPlan::class;
    }
}
