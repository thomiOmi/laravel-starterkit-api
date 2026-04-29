<?php

declare(strict_types=1);

namespace Modules\Subscription\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Subscription\Models\SubscriptionPlan;
use Modules\Subscription\Resources\SubscriptionPlanResource;

class SubscriptionPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:super-admin']);
    }

    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::all();

        return $this->successResponse(SubscriptionPlanResource::collection($plans));
    }

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

        $plan = SubscriptionPlan::create($data);

        return $this->successResponse(new SubscriptionPlanResource($plan), 'Subscription plan created successfully.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);

        return $this->successResponse(new SubscriptionPlanResource($plan));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);

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

        $plan->update($data);

        return $this->successResponse(new SubscriptionPlanResource($plan), 'Subscription plan updated successfully.');
    }

    public function destroy(string $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->delete();

        return $this->successResponse(null, 'Subscription plan deleted successfully.');
    }
}
