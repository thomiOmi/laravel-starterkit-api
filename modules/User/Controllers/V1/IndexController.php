<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\User\Actions\ListUsersAction;
use Modules\User\Filters\UserFilter;
use Modules\User\Models\User;
use Modules\User\Resources\UserResource;

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
    public function __invoke(Request $request, UserFilter $filter): SuccessResponse|ProblemResponse
    {
        /** @var Authenticatable&User $currentUser */
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

        return new SuccessResponse(
            'OK',
            __('general.retrieved', ['resource' => 'Users']),
            UserResource::collection($users),
            200,
        );
    }
}
