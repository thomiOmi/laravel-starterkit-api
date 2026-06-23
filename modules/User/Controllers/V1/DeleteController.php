<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Actions\DeleteUserAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class DeleteController
{
    public function __construct(
        private DeleteUserAction $deleteUser,
    ) {}

    /**
     * Remove the specified user from storage.
     *
     * @param  string  $user  The user ID.
     */
    public function __invoke(Request $request, string $user): JsonResponse|ProblemResponse
    {
        /** @var (Authenticatable&User)|null $currentUser */
        $currentUser = $request->user();

        if ($currentUser === null) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: SymfonyResponse::HTTP_FORBIDDEN,
                detail: __('general.forbidden'),
            );
        }

        $currentId = $currentUser->getKey();

        if ((is_string($currentId) || is_int($currentId) ? (string) $currentId : '') === $user) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: SymfonyResponse::HTTP_FORBIDDEN,
                detail: __('general.self_delete_forbidden'),
            );
        }

        if (! $currentUser->can('user.delete')) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: SymfonyResponse::HTTP_FORBIDDEN,
                detail: __('general.forbidden'),
            );
        }

        if ($this->deleteUser->handle($user)) {
            return new JsonResponse(null, SymfonyResponse::HTTP_NO_CONTENT);
        }

        return new ProblemResponse(
            title: 'Forbidden',
            status: SymfonyResponse::HTTP_FORBIDDEN,
            detail: __('general.delete_error', ['resource' => 'User']),
        );
    }
}
