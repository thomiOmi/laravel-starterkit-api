<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;
use Modules\User\Actions\ListUsersAction;
use Modules\User\Filters\UserFilter;
use Modules\User\Models\User;
use Modules\User\Resources\UserResource;

#[Group('User Management')]
/**
 * @authenticated
 */
final readonly class IndexController
{
    public function __construct(
        private ListUsersAction $listUsers
    ) {}

    /**
     * Display a paginated listing of the users.
     */
    #[QueryParameter(name: 'page[number]', description: 'The page number to start the pagination from.', type: 'integer', required: false, default: 1, example: 1)]
    #[QueryParameter(name: 'page[size]', description: 'The number of results that will be returned per page.', type: 'integer', required: false, default: 10, example: 10)]
    #[QueryParameter(name: 'search', description: 'Search keyword to filter users by name or email.', type: 'string', required: false, example: 'john')]
    #[QueryParameter(name: 'sort', description: 'Available sorts are `name`, `email`, `created_at`. Prefix with `-` for descending order. Comma-separated for multi-column sort.', type: 'string', required: false, example: '-created_at,name')]
    #[QueryParameter(name: 'filter[role]', description: 'The role name to filter users by.', type: 'string', required: false, example: 'admin')]
    #[QueryParameter(name: 'filter[status]', description: 'The status to filter users by. Possible values: `verified`, `unverified`.', type: 'string', required: false, example: 'verified')]
    #[Endpoint(operationId: 'listUsers', title: 'List Users')]
    #[Response(
        status: 200,
        description: 'Paginated list of users. Includes `meta` (pagination info) and `links` when applicable.',
        examples: [[
            'status' => 200,
            'title' => 'OK',
            'detail' => 'Users retrieved.',
            'data' => [
                ['id' => '01abcd', 'name' => 'John Doe', 'email' => 'john@example.com', 'roles' => ['admin'], 'permissions' => ['user.view']],
                ['id' => '02efgh', 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'roles' => ['user'], 'permissions' => []],
            ],
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
            'detail' => 'You must be authenticated to access this resource.',
        ]],
    )]
    #[Response(
        status: 403,
        description: 'Forbidden — the user does not have the required permissions to list users.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Forbidden',
            'status' => 403,
            'detail' => 'You are not authorised to perform this action.',
        ]],
    )]
    public function __invoke(Request $request, UserFilter $filter): SuccessResponse|ProblemResponse
    {
        /** @var User $currentUser */
        $currentUser = auth()->user();

        if (! $currentUser->can('user.view')) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: 403,
                detail: __('general.forbidden'),
            );
        }

        $users = $this->listUsers->handle(
            $filter,
            $request->integer('page.size', 10),
            $request->integer('page.number', 1),
        );

        $resource = UserResource::collection($users);
        /** @var array<string, mixed> $raw */
        $raw = $resource->toResponse($request)->getData(true);

        return new SuccessResponse(
            'OK',
            __('general.retrieved', ['resource' => 'Users']),
            $raw['data'] ?? [],
            200,
            array_filter([
                'meta' => $raw['meta'] ?? null,
                'links' => $raw['links'] ?? null,
            ], fn ($value) => $value !== null),
        );
    }
}
