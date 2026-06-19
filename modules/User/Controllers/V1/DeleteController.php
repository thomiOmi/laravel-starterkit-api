<?php

declare(strict_types=1);

namespace Modules\User\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Modules\User\Actions\DeleteUserAction;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

#[Group('User Management')]
/**
 * @authenticated
 */
final readonly class DeleteController
{
    public function __construct(
        private DeleteUserAction $deleteUser,
        private UserRepository $repository,
    ) {}

    /**
     * Remove the specified user from storage.
     *
     * @param  string  $user  The user ID.
     */
    #[Endpoint(operationId: 'deleteUser', title: 'Delete User')]
    #[Response(
        status: 204,
        description: 'User deleted successfully. No content is returned.',
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
        description: 'Forbidden — the user cannot be deleted (e.g., cannot delete self or protected user).',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Forbidden',
            'status' => 403,
            'detail' => 'You are not authorised to perform this action.',
        ]],
    )]
    #[Response(
        status: 404,
        description: 'User not found with the given ID.',
        mediaType: 'application/problem+json',
        examples: [[
            'type' => 'https://example.com/problems',
            'title' => 'Not Found',
            'status' => 404,
            'detail' => 'The requested resource does not exist.',
        ]],
    )]
    public function __invoke(string $user): JsonResponse|ProblemResponse
    {
        /** @var User $currentUser */
        $currentUser = auth()->user();

        if (! $currentUser->can('user.delete')) {
            return new ProblemResponse(
                title: 'Forbidden',
                status: 403,
                detail: __('general.forbidden'),
            );
        }

        $model = $this->repository->findById($user);

        if (! $model) {
            return new ProblemResponse(
                title: 'Not Found',
                status: 404,
                detail: __('general.not_found', ['resource' => 'User']),
            );
        }

        if ($this->deleteUser->handle($model)) {
            return new JsonResponse(null, SymfonyResponse::HTTP_NO_CONTENT);
        }

        return new ProblemResponse(
            title: 'Forbidden',
            status: 403,
            detail: __('general.delete_error', ['resource' => 'User']),
        );
    }
}
