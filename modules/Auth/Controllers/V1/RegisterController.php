<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\RegisterAction;

class RegisterController extends Controller
{
    use ApiResponser;

    public function __construct(
        protected RegisterAction $registerAction
    ) {}

    public function register(Request $request): JsonResponse
    {
        $user = $this->registerAction->execute($request->all());
        $user->sendEmailVerificationNotification();

        return $this->successResponse(
            ['user' => $user],
            __('auth.registered'),
            201
        );
    }
}
