<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Actions\DeleteDeviceAction;
use Modules\IAM\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class DeleteDeviceController extends Controller
{
    public function __construct(
        private DeleteDeviceAction $deleteDevice
    ) {}

    /**
     * Delete a specific device (token) of the authenticated user.
     *
     * @return SuccessResponse<null>
     */
    public function __invoke(#[CurrentUser] User $currentUser, PersonalAccessToken $device): SuccessResponse
    {
        $this->deleteDevice->handle($currentUser, $device);

        return new SuccessResponse(
            data: null,
            title: __('general.resource_deleted', ['resource' => 'Device']),
            detail: __('general.resource_deleted', ['resource' => 'Device']),
            status: SymfonyResponse::HTTP_OK,
        );
    }
}
