<?php

declare(strict_types=1);

namespace Modules\User\Controllers\Auth;

use App\Http\Responses\JsonDataResponse;
use App\Http\Responses\ProblemResponse;
use Illuminate\Http\Request;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class VerifyEmailController
{
    public function __invoke(Request $request, string $id, string $hash): JsonDataResponse|ProblemResponse
    {
        $user = User::withTrashed()->find($id);

        if (! $user instanceof User) {
            return new ProblemResponse(
                title: __('auth.not_found'),
                status: Response::HTTP_NOT_FOUND,
                detail: 'User not found.',
            );
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return new ProblemResponse(
                title: __('auth.forbidden'),
                status: Response::HTTP_FORBIDDEN,
                detail: 'Invalid verification hash.',
            );
        }

        if ($user->hasVerifiedEmail()) {
            return new JsonDataResponse(
                data: ['verified' => true],
                message: __('auth.verified'),
            );
        }

        $user->markEmailAsVerified();

        return new JsonDataResponse(
            data: ['verified' => true],
            message: __('auth.verified'),
        );
    }
}
