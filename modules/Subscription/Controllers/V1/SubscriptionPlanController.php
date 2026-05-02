<?php

declare(strict_types=1);

namespace Modules\Subscription\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Subscription\Repositories\SubscriptionPlanRepository;
use Modules\Subscription\Resources\SubscriptionPlanResource;

/**
 * @tags Subscription Plans
 */
class SubscriptionPlanController extends Controller
{
    public function __construct(
        protected SubscriptionPlanRepository $repository
    ) {
        $this->middleware(['auth:sanctum', 'role:super-admin']);
    }

    /**
     * List Plans
     *
     * Get all subscription plans.
     */
    public function index(): JsonResponse
    {
        $plans = $this->repository->all();

        return $this->successResponse(SubscriptionPlanResource::collection($plans));
    }

    /**
     * Create Plan
     *
     * Create a new subscription plan.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|integer|min:1',
            'features' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $data['slug'] = Str::slug($data['name']);

        $plan = $this->repository->create($data);

        return $this->successResponse(new SubscriptionPlanResource($plan), 'Subscription plan created successfully.', 201);
    }

    /**
     * Show Plan
     *
     * Get a subscription plan by ID.
     */
    public function show(string $id): JsonResponse
    {
        $plan = $this->repository->findById($id);

        return $this->successResponse(new SubscriptionPlanResource($plan));
    }

    /**
     * Update Plan
     *
     * Update an existing subscription plan.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'billing_cycle' => 'sometimes|integer|min:1',
            'features' => 'sometimes|array',
            'is_active' => 'boolean',
        ]);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $plan = $this->repository->update($id, $data);

        return $this->successResponse(new SubscriptionPlanResource($plan), 'Subscription plan updated successfully.');
    }

    /**
     * Delete Plan
     *
     * Delete a subscription plan.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->successResponse(null, 'Subscription plan deleted successfully.');
    }
}
