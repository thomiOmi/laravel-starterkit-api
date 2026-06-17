<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Modules\Role\Actions\BulkRestoreRolesAction;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('Role Management')]
/**
 * @authenticated
 */
final readonly class BulkRestoreRolesController
{
    public function __construct(
        private BulkRestoreRolesAction $bulkRestoreRoles,
    ) {}

    /**
     * Perform bulk restore on roles.
     */
    #[Endpoint(operationId: 'bulkRestoreRoles', title: 'Bulk Restore Roles')]
    #[Response(
        status: 200,
        description: 'Roles restored successfully. Returns the count of restored records.',
        examples: [[
            'status' => 200,
            'message' => 'Roles restored.',
            'data' => ['count' => 3],
        ]],
    )]
    #[Response(
        status: 401,
        description: 'Authentication required. The request lacks a valid Bearer token.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Unauthenticated',
            'status' => 401,
            'message' => 'Unauthenticated',
            'detail' => 'You must be authenticated to access this resource.',
        ]],
    )]
    #[Response(
        status: 403,
        description: 'Forbidden — the user does not have the required permissions to restore roles.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Forbidden',
            'status' => 403,
            'message' => 'Forbidden',
            'detail' => 'You are not authorised to perform this action.',
        ]],
    )]
    #[Response(
        status: 422,
        description: 'Validation error — invalid IDs payload.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Validation Error',
            'status' => 422,
            'message' => 'Validation Error',
            'detail' => 'The given data was invalid.',
            'errors' => ['ids' => ['The ids field is required.']],
        ]],
    )]
    public function __invoke(BulkActionRequest $request): JsonResponse
    {
        /** @var array{ids: array<int, string|int>} $validated */
        $validated = $request->validated();

        $count = $this->bulkRestoreRoles->handle($validated['ids']);

        return new JsonResponse(
            [
                'status' => SymfonyResponse::HTTP_OK,
                'message' => __('general.restored', ['resource' => 'Roles']),
                'data' => ['count' => $count],
            ],
            SymfonyResponse::HTTP_OK,
        );
    }
}
