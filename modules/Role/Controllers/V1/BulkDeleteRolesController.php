<?php

declare(strict_types=1);

namespace Modules\Role\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\Role\Actions\BulkDeleteRolesAction;

#[Group('Role Management')]
/**
 * @authenticated
 */
final readonly class BulkDeleteRolesController
{
    public function __construct(
        private BulkDeleteRolesAction $bulkDeleteRoles,
    ) {}

    /**
     * Perform bulk delete on roles.
     */
    #[Endpoint(operationId: 'bulkDeleteRoles', title: 'Bulk Delete Roles')]
    #[Response(
        status: 200,
        description: 'Roles deleted successfully. Returns the count of deleted records.',
        examples: [[
            'status' => 200,
            'message' => 'Roles deleted.',
            'data' => ['count' => 2],
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
        description: 'Forbidden — the user does not have the required permissions to delete roles.',
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
    public function __invoke(BulkActionRequest $request): SuccessResponse
    {
        /** @var array{ids: array<int, string|int>} $validated */
        $validated = $request->validated();

        $count = $this->bulkDeleteRoles->handle($validated['ids']);

        return new SuccessResponse(
            'OK',
            __('general.deleted', ['resource' => 'Roles']),
            ['count' => $count],
        );
    }
}
