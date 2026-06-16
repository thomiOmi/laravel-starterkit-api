<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as ScrambleResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\DeleteDeviceAction;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

#[Group('Auth')]
/**
 * @authenticated
 */
final readonly class DeleteDeviceController
{
    public function __construct(
        private DeleteDeviceAction $deleteDevice
    ) {}

    #[Endpoint(operationId: 'deleteDevice', title: 'Delete Device')]
    #[ScrambleResponse(status: 204, description: 'Device deleted successfully')]
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
