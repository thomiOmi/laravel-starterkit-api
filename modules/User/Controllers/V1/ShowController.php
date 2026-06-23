<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\User\Actions\ShowUserAction;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class ShowController
{
    public function __construct(
        private ShowUserAction $showUser
    ) {}

    /**
     * Display the specified user.
     */
    public function __invoke(Request $request, string $user): SuccessResponse|ProblemResponse
    {
        /** @var (Authenticatable&\Modules\User\Models\User)|null $currentUser */
        $currentUser = $request->user();

        if ($currentUser === null || ((is_scalar($currentUser->getKey()) ? (string) $currentUser->getKey() : '') !== $user && ! $currentUser->can('user.view'))) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: Response::HTTP_FORBIDDEN,
                detail: __('general.forbidden'),
            );
        }

        $user = $this->showUser->handle($user);

        if (! $user) {
            return new ProblemResponse(
                title: 'Not Found',
                status: Response::HTTP_NOT_FOUND,
                detail: __('general.not_found', ['resource' => 'User']),
            );
        }

        return new SuccessResponse(
            'OK',
            __('general.retrieved', ['resource' => 'User']),
            new UserResource($user),
        );
    }
}
