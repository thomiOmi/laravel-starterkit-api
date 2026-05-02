<?php

declare(strict_types=1);

namespace Modules\Subscription\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Modules\Subscription\Models\SubscriptionPlan;

/**
 * @property-read SubscriptionPlan $resource
 *
 * @mixin SubscriptionPlan
 */
class SubscriptionPlanResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'description' => $this->resource->description,
            'price' => $this->resource->price,
            'billing_cycle' => $this->resource->billing_cycle,
            'features' => $this->resource->features,
            'is_active' => $this->resource->is_active,
            'created_at' => $this->formatDate($this->resource->created_at),
            'updated_at' => $this->formatDate($this->resource->updated_at),
        ];
    }
}
