<?php

declare(strict_types=1);

namespace Modules\Subscription\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Subscription\Repositories\SubscriptionPlanRepository;
use Modules\Subscription\Repositories\SubscriptionRepository;

/**
 * @tags Tenant Subscriptions
 */
class TenantSubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionRepository $subscriptionRepository,
        protected SubscriptionPlanRepository $planRepository
    ) {
        $this->middleware(['auth:sanctum', 'role:super-admin']);
    }

    /**
     * Assign Plan
     *
     * Manually assign a plan to a tenant.
     */
    public function assign(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'plan_id' => 'required|exists:subscription_plans,id',
            'status' => 'sometimes|string|in:active,trial,expired',
            'duration_days' => 'sometimes|integer|min:1',
        ]);

        $plan = $this->planRepository->findById($request->plan_id);
        $duration = $request->duration_days ?: $plan->billing_cycle;

        // Cancel previous active subscriptions
        $this->subscriptionRepository->cancelActiveSubscriptions($request->tenant_id);

        $subscription = $this->subscriptionRepository->create([
            'tenant_id' => $request->tenant_id,
            'plan_id' => $plan->id,
            'status' => $request->status ?: 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays($duration),
        ]);

        return $this->successResponse($subscription, 'Plan assigned to tenant successfully.', 201);
    }
}
