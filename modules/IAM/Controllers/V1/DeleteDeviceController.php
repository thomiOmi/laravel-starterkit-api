<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Container\Attributes\CurrentUser;
use Modules\IAM\Actions\DeleteDeviceAction;
use Modules\IAM\Models\User;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class DeleteDeviceController
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

        return new SuccessResponse(null, status: SymfonyResponse::HTTP_NO_CONTENT);
    }
}
