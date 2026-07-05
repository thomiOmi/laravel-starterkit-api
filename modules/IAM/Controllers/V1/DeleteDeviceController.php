<?php

declare(strict_types=1);

namespace Modules\IAM\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Contracts\Auth\Authenticatable;
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
     * @return SuccessResponse<null>
     */
    public function __invoke(Request $request, string $device): SuccessResponse
    {
        /** @var (Authenticatable&User) $currentUser */
        $currentUser = $request->user();

        $this->deleteDevice->handle($currentUser, $device);

        return new SuccessResponse(null, status: SymfonyResponse::HTTP_NO_CONTENT);
    }
}
