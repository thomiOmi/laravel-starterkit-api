<?php

declare(strict_types=1);

namespace Modules\User\Controllers\Auth;

use App\Http\Responses\JsonDataResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Models\User;

/**
 * @tags Auth
 */
final readonly class ResendVerificationController
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return new JsonDataResponse(
                data: null,
                message: __('auth.verified'),
            );
        }

        $user->sendEmailVerificationNotification();

        return new JsonDataResponse(
            data: null,
            message: __('auth.verification_link_sent'),
        );
    }
}
