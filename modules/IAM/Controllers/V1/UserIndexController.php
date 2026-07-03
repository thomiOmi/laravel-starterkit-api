<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IAM\Actions\ListUsersAction;
use Modules\IAM\Filters\UserFilter;
use Modules\IAM\Models\User;
use Modules\IAM\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class UserIndexController
{
    public function __construct(
        private ListUsersAction $listUsers
    ) {}

    /**
     * Display a paginated listing of the users.
     *
     * @return SuccessResponse<AnonymousResourceCollection>|ProblemResponse
     */
    public function __invoke(Request $request, UserFilter $filter): SuccessResponse|ProblemResponse
    {
        /** @var (Authenticatable&User)|null $currentUser */
        $currentUser = $request->user();

        if ($currentUser === null) {
            return new ProblemResponse(
                title: 'Unauthenticated',
                status: Response::HTTP_UNAUTHORIZED,
                detail: __('auth.unauthenticated'),
            );
        }

        if (! $currentUser->can('user.view')) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: Response::HTTP_FORBIDDEN,
                detail: __('general.forbidden'),
            );
        }

        $users = $this->listUsers->handle(
            $filter,
            $request->integer('page.size', 10),
            $request->integer('page.number', 1),
        );

        return new SuccessResponse(
            data: UserResource::collection($users),
            title: 'OK',
            detail: __('general.retrieved', ['resource' => 'Users']),
        );
    }
}
