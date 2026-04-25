<?php

declare(strict_types=1);

namespace Modules\ApiKey\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiKey\Actions\CreateApiKeyAction;
use Modules\ApiKey\DTOs\ApiKeyDTO;
use Modules\ApiKey\Repositories\ApiKeyRepository;
use Modules\ApiKey\Requests\ApiKeyRequest;
use Modules\ApiKey\Resources\ApiKeyResource;

class ApiKeyController extends Controller
{
    /**
     * ApiKeyController constructor.
     */
    public function __construct(protected ApiKeyRepository $repository) {}

    /**
     * List all API Keys for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var string $userId */
        $userId = $request->user()->id;
        $apiKeys = $this->repository->findByUserId($userId);

        return $this->successResponse(
            ApiKeyResource::collection($apiKeys),
            'API Keys retrieved successfully.'
        );
    }

    /**
     * Store a new API Key.
     */
    public function store(ApiKeyRequest $request, CreateApiKeyAction $action): JsonResponse
    {
        $dto = ApiKeyDTO::fromRequest($request);
        /** @var string $userId */
        $userId = $request->user()->id;
        $result = $action->execute($dto, $userId);

        return $this->successResponse([
            'api_key' => new ApiKeyResource($result['api_key']),
            'plain_text_key' => $result['plain_text_key'],
        ], 'API Key created successfully. Please copy it now as it won\'t be shown again.', 201);
    }

    /**
     * Revoke (delete) an API Key.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->successResponse(null, 'API Key revoked successfully.');
    }
}
