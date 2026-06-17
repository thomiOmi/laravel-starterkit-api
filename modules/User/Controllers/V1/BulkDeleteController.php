<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Requests\BulkActionRequest;
use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Modules\User\Actions\BulkDeleteUsersAction;

#[Group('User Management')]
/**
 * @authenticated
 */
final readonly class BulkDeleteController
{
    public function __construct(
        private BulkDeleteUsersAction $bulkDeleteUsers,
    ) {}

    /**
     * Perform bulk delete on users.
     */
    #[Endpoint(operationId: 'bulkDeleteUsers', title: 'Bulk Delete Users')]
    #[Response(
        status: 200,
        description: 'Users deleted successfully. Returns the count of deleted records.',
        examples: [[
            'status' => 200,
            'message' => 'Users deleted.',
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
        description: 'Forbidden — the user does not have the required permissions to bulk delete users.',
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

        $count = $this->bulkDeleteUsers->handle($validated['ids']);

        return new SuccessResponse(
            'OK',
            __('general.deleted', ['resource' => 'Users']),
            ['count' => $count],
        );
    }
}
