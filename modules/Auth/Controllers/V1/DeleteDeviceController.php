<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\DeleteDeviceAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * @authenticated
 */
final readonly class DeleteDeviceController
{
    public function __construct(
        private DeleteDeviceAction $deleteDevice
    ) {}

    public function __invoke(Request $request, string $device): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->deleteDevice->handle($user, $device);

        return new JsonResponse(null, SymfonyResponse::HTTP_NO_CONTENT);
    }
}
