<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Actions\ListUsersAction;
use Modules\IAM\Requests\V1\UserListRequest;
use Modules\IAM\Resources\UserResource;

final readonly class UserListController
{
    public function __construct(
        private ListUsersAction $listUsers
    ) {}

    /**
     * Display a paginated listing of the users.
     *
     * @return SuccessResponse<AnonymousResourceCollection>
     */
    public function __invoke(UserListRequest $request): SuccessResponse
    {
        $users = $this->listUsers->handle(
            $request->integer('page.size', 10),
            $request->integer('page.number', 1),
        );

        return new SuccessResponse(
            data: UserResource::collection($users),
            title: 'OK',
            detail: __('general.resource_retrieved', ['resource' => 'Users']),
        );
    }
}
