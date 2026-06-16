<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\JsonDataResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;
use Modules\Auth\Actions\ListDevicesAction;
use Modules\Auth\Resources\DeviceResource;
use Modules\User\Models\User;

#[Group('Auth')]
/**
 * @authenticated
 */
final readonly class ListDevicesController
{
    public function __construct(
        private ListDevicesAction $listDevices
    ) {}

    #[Endpoint(operationId: 'listDevices', title: 'List Devices')]
    #[Response(status: 200, description: 'List of devices retrieved', examples: ['status' => 200, 'message' => 'Devices retrieved.', 'data' => [['id' => 1, 'name' => 'test-device', 'last_used_at' => '2026-06-16 20:00:00', 'is_current' => true], ['id' => 2, 'name' => 'second-device', 'last_used_at' => '2026-06-15 10:00:00', 'is_current' => false]]])]
    public function __invoke(Request $request): JsonDataResponse
    {
        /** @var User $user */
        $user = $request->user();

        $devices = $this->listDevices->handle($user);

        return new JsonDataResponse(
            data: DeviceResource::collection($devices),
            message: __('messages.retrieved', ['resource' => 'Devices'])
        );
    }
}
