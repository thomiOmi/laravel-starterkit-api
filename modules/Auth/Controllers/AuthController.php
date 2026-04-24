<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Actions\ForgotPasswordAction;
use Modules\Auth\Actions\LoginAction;
use Modules\Auth\Actions\RegisterAction;
use Modules\Auth\Actions\ResendVerificationEmailAction;
use Modules\Auth\Actions\ResetPasswordAction;
use Modules\Auth\Actions\VerifyEmailAction;
use Modules\Auth\DTOs\ForgotPasswordDTO;
use Modules\Auth\DTOs\LoginDTO;
use Modules\Auth\DTOs\ResetPasswordDTO;
use Modules\Auth\DTOs\VerifyEmailDTO;
use Modules\Auth\Requests\ForgotPasswordRequest;
use Modules\Auth\Requests\LoginRequest;
use Modules\Auth\Requests\RegisterRequest;
use Modules\Auth\Requests\ResetPasswordRequest;
use Modules\User\DTOs\UserDTO;
use Modules\User\Resources\UserResource;

/**
 * @tags Auth
 */
class AuthController extends Controller
{
    /**
     * Login
     *
     * Handle an authentication attempt.
     *
     * @param  LoginRequest  $request  The login request.
     * @param  LoginAction  $action  The login action.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $request->ensureIsNotRateLimited();
        $dto = LoginDTO::fromRequest($request);
        $result = $action->execute($dto, $request);

        /**
         * Success response with user and access token.
         */
        return $this->successResponse(
            [
                'user' => new UserResource($result['user']->load(['roles.permissions', 'permissions'])),
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
            ],
            'Login successful'
        );
    }

    /**
     * Register
     *
     * Handle a registration request for the application.
     *
     * @param  RegisterRequest  $request  The user request.
     * @param  RegisterAction  $action  The register action.
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request, RegisterAction $action): JsonResponse
    {
        $dto = UserDTO::fromRequest($request);
        $result = $action->execute($dto);

        /**
         * Success response with user and access token.
         */
        return $this->successResponse(
            [
                'user' => new UserResource($result['user']->load(['roles.permissions', 'permissions'])),
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'],
            ],
            'User registered successfully. Please verify your email.'
        );
    }

    /**
     * Logout
     *
     * Log the user out of the application.
     *
     * @param  Request  $request  The request.
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the token that was used to authenticate the request
        $request->user()?->currentAccessToken()?->delete();

        return $this->successResponse(null, 'Logout successful');
    }

    /**
     * Me
     *
     * Get the authenticated user profile.
     *
     * @param  Request  $request  The request.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            new UserResource($request->user()->load(['roles.permissions', 'permissions'])),
            'User profile retrieved successfully'
        );
    }

    /**
     * Forgot Password
     *
     * Send a reset link to the given user.
     *
     * @param  ForgotPasswordRequest  $request  The user request.
     * @param  ForgotPasswordAction  $action  The forgot password action.
     *
     * @unauthenticated
     */
    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action): JsonResponse
    {
        $dto = ForgotPasswordDTO::fromRequest($request);
        $message = $action->execute($dto);

        return $this->successResponse(null, $message);
    }

    /**
     * Reset Password
     *
     * Reset the given user's password.
     *
     * @param  ResetPasswordRequest  $request  The user request.
     * @param  ResetPasswordAction  $action  The reset password action.
     *
     * @unauthenticated
     */
    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        $dto = ResetPasswordDTO::fromRequest($request);
        $message = $action->execute($dto);

        return $this->successResponse(null, $message);
    }

    /**
     * Verify Email
     *
     * Mark the user's email address as verified.
     *
     * @param  Request  $request  The request.
     * @param  VerifyEmailAction  $action  The verify email action.
     */
    public function verifyEmail(Request $request, VerifyEmailAction $action): JsonResponse
    {
        $dto = VerifyEmailDTO::fromRequest($request);
        $message = $action->execute($dto);

        return $this->successResponse(null, $message);
    }

    /**
     * Resend Email Verification
     *
     * Resend the email verification notification.
     *
     * @param  Request  $request  The request.
     * @param  ResendVerificationEmailAction  $action  The resend verification email action.
     */
    public function resendVerificationEmail(Request $request, ResendVerificationEmailAction $action): JsonResponse
    {
        $message = $action->execute($request->user());

        return $this->successResponse(null, $message);
    }
}
