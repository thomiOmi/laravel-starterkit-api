<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\DataResponse;
use Modules\Auth\Actions\RegisterAction;
use Modules\Auth\Requests\V1\RegisterRequest;
use Modules\Auth\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Auth
 */
final readonly class RegisterController
{
    public function __construct(
        private RegisterAction $registerAction
    ) {}

    public function __invoke(RegisterRequest $request): DataResponse
    {
        $user = $this->registerAction->handle($request->payload());

        return new DataResponse(
            data: new UserResource($user),
            status: Response::HTTP_CREATED,
            message: __('auth.registered')
        );
    }
}
