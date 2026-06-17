<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Modules\Auth\Actions\RegisterAction;
use Modules\Auth\Requests\V1\RegisterRequest;
use Modules\User\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;

#[Group('Auth')]
final readonly class RegisterController
{
    public function __construct(
        private RegisterAction $registerAction
    ) {}

    #[Endpoint(operationId: 'register', title: 'Register')]
    public function __invoke(RegisterRequest $request): UserResource
    {
        $user = $this->registerAction->handle($request->payload());

        return (new UserResource($user))->additional([
            'status_code' => Response::HTTP_CREATED,
            'message' => __('auth.registered'),
        ]);
    }
}
