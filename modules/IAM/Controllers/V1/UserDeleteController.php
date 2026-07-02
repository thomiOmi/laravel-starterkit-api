<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\IAM\Actions\DeleteUserAction;
use Modules\IAM\Models\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class UserDeleteController
{
    public function __construct(
        private DeleteUserAction $deleteUser,
    ) {}

    /**
     * Remove the specified user from storage.
     *
     * @param  string  $id  The user ID.
     */
    public function __invoke(Request $request, string $id): JsonResponse|ProblemResponse
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

        $currentUserId = $currentUser->getKey();

        if ((is_string($currentUserId) || is_int($currentUserId) ? (string) $currentUserId : '') === $id) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: Response::HTTP_FORBIDDEN,
                detail: __('general.self_delete_forbidden'),
            );
        }

        if (! $currentUser->can('user.delete')) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: Response::HTTP_FORBIDDEN,
                detail: __('general.forbidden'),
            );
        }

        if ($this->deleteUser->handle($id)) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new ProblemResponse(
            title: 'Forbidden',
            status: Response::HTTP_FORBIDDEN,
            detail: __('general.delete_error', ['resource' => 'User']),
        );
    }
}
