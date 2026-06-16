<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Role\Actions\ListPermissionsAction;
use Modules\Role\Filters\PermissionFilter;
use Modules\Role\Resources\PermissionResource;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Permission Management')]
/**
 * @authenticated
 */
final readonly class PermissionIndexController
{
    public function __construct(
        private ListPermissionsAction $listPermissions
    ) {}

    /**
     * Display a paginated listing of permissions.
     */
    #[Endpoint(operationId: 'listPermissions', title: 'List Permissions')]
    #[Response(status: 200, description: 'Paginated list of permissions', examples: ['status' => 200, 'message' => 'Permissions retrieved.', 'data' => [['id' => 1, 'name' => 'user.list', 'guard_name' => 'web']]])]
    public function __invoke(Request $request, PermissionFilter $filter): JsonResponse
    {
        $permissions = $this->listPermissions->handle($filter, $request->integer('per_page', 20));

        $resource = PermissionResource::collection($permissions);
        /** @var array<string, mixed> $raw */
        $raw = $resource->toResponse($request)->getData(true);

        return new JsonResponse(
            array_filter([
                'status' => SymfonyResponse::HTTP_OK,
                'message' => __('general.retrieved', ['resource' => 'Permissions']),
                'data' => $raw['data'] ?? [],
                'meta' => $raw['meta'] ?? null,
                'links' => $raw['links'] ?? null,
            ], fn ($value) => $value !== null),
            SymfonyResponse::HTTP_OK,
        );
    }
}
