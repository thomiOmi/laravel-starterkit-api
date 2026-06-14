<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\DeleteDeviceAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Auth
 */
final readonly class DeleteDeviceController
{
    public function __construct(
        private DeleteDeviceAction $deleteDevice
    ) {}

    public function __invoke(Request $request, string $device): JsonDataResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->deleteDevice->handle($user, $device);

        return new JsonDataResponse(
            data: null,
            status: Response::HTTP_NO_CONTENT,
            message: __('auth.device_logout_success')
        );
    }
}
