<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\ForgotPasswordAction;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\Actions\RegisterAction;
use Modules\Auth\Actions\ResetPasswordAction;
use Modules\Auth\DTOs\ForgotPasswordDTO;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\DTOs\ResetPasswordDTO;
use Modules\Auth\Requests\ForgotPasswordRequest;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\RegisterRequest;
use Modules\Auth\Requests\ResetPasswordRequest;
use Modules\User\DTOs\UserDTO;
use Modules\User\Models\User;
use Modules\User\Resources\UserResource;

class AuthController extends Controller
{
    /**
     * Handle a registration request for the application.
     *
     * @return JsonResponse
     */
    public function register(RegisterRequest $request, RegisterAction $action)
    {
        $dto = UserDTO::fromRequest($request);
        $result = $action->execute($dto);

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'access_token' => $result['access_token'],
            'token_type' => $result['token_type'],
        ], 'User registered successfully. Please verify your email.');
    }

    /**
     * Handle an authentication attempt.
     *
     * @return JsonResponse
     */
    public function login(LoginRequest $request, LoginAction $action)
    {
        $dto = LoginDTO::fromRequest($request);
        $result = $action->execute($dto);

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'access_token' => $result['access_token'],
            'token_type' => $result['token_type'],
        ], 'Login successful');
    }

    /**
     * Log the user out of the application.
     *
     * @return JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the request
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logout successful');
    }

    /**
     * Get the authenticated user profile.
     *
     * @return JsonResponse
     */
    public function me(Request $request)
    {
        return $this->successResponse(new UserResource($request->user()), 'User profile retrieved successfully');
    }

    /**
     * Send a reset link to the given user.
     *
     * @return JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action)
    {
        $dto = ForgotPasswordDTO::fromRequest($request);
        $message = $action->execute($dto);

        return $this->successResponse(null, $message);
    }

    /**
     * Reset the given user's password.
     *
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action)
    {
        $dto = ResetPasswordDTO::fromRequest($request);
        $message = $action->execute($dto);

        return $this->successResponse(null, $message);
    }

    /**
     * Mark the user's email address as verified.
     *
     * @return JsonResponse
     */
    public function verifyEmail(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return $this->errorResponse('Invalid verification link.', 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(null, 'Email already verified.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->successResponse(null, 'Email verified successfully');
    }

    /**
     * Resend the email verification notification.
     *
     * @return JsonResponse
     */
    public function resendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->errorResponse('Email already verified.', 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return $this->successResponse(null, 'Verification link sent');
    }
}
