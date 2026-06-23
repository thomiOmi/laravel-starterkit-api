<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Responses\SuccessResponse;
use Illuminate\Auth\Events\Registered;
use Modules\Auth\Actions\RegisterAction;
use Modules\Auth\Requests\V1\RegisterRequest;
use Modules\User\Resources\UserResource;

final readonly class RegisterController
{
    public function __construct(
        private RegisterAction $registerAction
    ) {}

    public function __invoke(RegisterRequest $request): SuccessResponse
    {
        $result = $this->registerAction->handle($request->payload());

        event(new Registered($result['user']));

        return new SuccessResponse(
            'Created',
            __('auth.registered'),
            [
                'user' => new UserResource($result['user']),
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
            ],
            201,
        );
    }
}
