<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\ProblemResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\IAM\Actions\DeleteDeviceAction;
use Modules\IAM\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class DeleteDeviceController
{
    public function __construct(
        private DeleteDeviceAction $deleteDevice
    ) {}

    /**
     * Delete an authenticated device session.
     *
     * @param  string  $device  The device ID.
     *
     * @throws AuthenticationException Full authentication is required.
     * @throws ModelNotFoundException The specified device was not found.
     */
    public function __invoke(Request $request, string $device): JsonResponse|ProblemResponse
    {
        /** @var (Authenticatable&User)|null $currentUser */
        $currentUser = $request->user();

        if ($currentUser === null) {
            return new ProblemResponse(
                title: 'Unauthenticated',
                status: SymfonyResponse::HTTP_UNAUTHORIZED,
                detail: __('auth.unauthenticated'),
            );
        }

        $this->deleteDevice->handle($currentUser, $device);

        return new JsonResponse(null, SymfonyResponse::HTTP_NO_CONTENT);
    }
}
