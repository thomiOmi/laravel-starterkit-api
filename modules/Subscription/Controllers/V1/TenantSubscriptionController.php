<?php

declare(strict_types=1);

namespace Modules\Subscription\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Subscription\Models\Subscription;
use Modules\Subscription\Models\SubscriptionPlan;
use Modules\Tenant\Models\Tenant;

class TenantSubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:super-admin']);
    }

    /**
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

        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        $duration = $request->duration_days ?: $plan->billing_cycle;

        // Cancel previous active subscriptions
        Subscription::where('tenant_id', $request->tenant_id)
            ->where('status', 'active')
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

        $subscription = Subscription::create([
            'tenant_id' => $request->tenant_id,
            'plan_id' => $plan->id,
            'status' => $request->status ?: 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays($duration),
        ]);

        return $this->successResponse($subscription, 'Plan assigned to tenant successfully.', 201);
    }
}
