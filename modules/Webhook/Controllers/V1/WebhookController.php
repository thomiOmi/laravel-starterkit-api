<?php

declare(strict_types=1);

namespace Modules\Webhook\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Webhook\Repositories\WebhookRepository;
use Modules\Webhook\Resources\WebhookResource;

class WebhookController extends Controller
{
    public function __construct(
        protected WebhookRepository $repository
    ) {}

    public function index(): JsonResponse
    {
        $webhooks = $this->repository->all();

        return $this->successResponse(WebhookResource::collection($webhooks));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string',
            'url' => 'required|url',
            'secret' => 'nullable|string',
            'events' => 'required|array',
            'events.*' => 'required|string',
        ]);

        $webhook = $this->repository->create($data);

        return $this->successResponse(new WebhookResource($webhook), 'Webhook created successfully', 201);
    }

    public function show(string $id): JsonResponse
    {
        $webhook = $this->repository->findById($id);

        return $this->successResponse(new WebhookResource($webhook));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->successResponse(null, 'Webhook deleted successfully');
    }
}
