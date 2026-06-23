<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\User\Actions\ShowUserAction;
use Modules\User\Models\User;
use Modules\User\Resources\UserResource;

/**
 * @authenticated
 */
final readonly class ShowController
{
    public function __construct(
        private ShowUserAction $showUser
    ) {}

    /**
     * Display the specified user.
     */
    public function __invoke(string $user): SuccessResponse|ProblemResponse
    {
        /** @var Authenticatable&User $currentUser */
        $currentUser = auth()->user();

        if ($currentUser->getKey() !== $user && ! $currentUser->can('user.view')) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: 403,
                detail: __('general.forbidden'),
            );
        }

        $user = $this->showUser->handle($user);

        if (! $user) {
            return new ProblemResponse(
                title: 'Not Found',
                status: 404,
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
