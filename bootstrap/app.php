<?php

declare(strict_types=1);

use App\Http\Middleware\FeatureFlagMiddleware;
use App\Http\Middleware\SetLocaleMiddleware;
use App\Http\Middleware\Sunset;
use App\Http\Middleware\TraceIdMiddleware;
use App\Http\Responses\ProblemResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'feature.flag' => FeatureFlagMiddleware::class,
            'sunset' => Sunset::class,
            'trace.id' => TraceIdMiddleware::class,
        ]);

        $middleware->priority([
            EnsureFrontendRequestsAreStateful::class,
            Authenticate::class,
            AuthenticatesRequests::class,
        ]);

        $middleware->redirectGuestsTo(null);

        $middleware->append(SetLocaleMiddleware::class);
        $middleware->prependToGroup('api', TraceIdMiddleware::class);

        $middleware->trustHosts(at: function (): array {
            /** @var array<int, string> $hosts */
            $hosts = config()->array('app.trusted_hosts', []);

            return $hosts;
        });

        $middleware->statefulApi();
        // $middleware->throttleApi(); // We will define custom throttle in routes
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e): bool {
            return $request->is('api/*') || $request->expectsJson();
        });

        // Validation Exception (422)
        $exceptions->render(function (ValidationException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                typeKey: 'validation',
                title: __('auth.http_validation_failed'),
                status: $e->getCode() ?: Response::HTTP_UNPROCESSABLE_ENTITY,
                detail: $e->getMessage() ?: 'The given data was invalid.',
                extensions: [
                    'errors' => $e->errors(),
                ]
            );
        });

        // Authentication Exception (401)
        $exceptions->render(function (AuthenticationException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                typeKey: 'unauthenticated',
                title: __('auth.http_unauthorized'),
                status: $e->getCode() ?: Response::HTTP_UNAUTHORIZED,
                detail: $e->getMessage() ?: 'You must be authenticated to access this resource.',
            );
        });

        // Access Denied / Forbidden (403)
        $exceptions->render(function (AccessDeniedHttpException|InvalidSignatureException|AuthorizationException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                typeKey: 'forbidden',
                title: __('auth.http_forbidden'),
                status: $e->getCode() ?: Response::HTTP_FORBIDDEN,
                detail: $e instanceof InvalidSignatureException
                    ? 'The request signature is invalid or has expired.'
                    : 'You are not authorised to perform this action.'
            );
        });

        // Not Found Exception (404)
        $exceptions->render(function (NotFoundHttpException|ModelNotFoundException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                typeKey: 'not_found',
                title: __('auth.http_not_found'),
                status: $e->getCode() ?: Response::HTTP_NOT_FOUND,
                detail: $e->getMessage() ?: 'The requested URL does not exist.',
            );
        });

        // Too Many Requests Exception (429)
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                typeKey: 'rate_limited',
                title: __('auth.http_too_many_requests'),
                status: $e->getCode() ?: Response::HTTP_TOO_MANY_REQUESTS,
                detail: $e->getMessage() ?: 'You have exceeded the request rate limit. Please try again later.',
            );
        });

        // Invalid Argument Exception (400)
        $exceptions->render(function (InvalidArgumentException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                typeKey: 'bad_request',
                title: __('auth.http_bad_request'),
                status: $e->getCode() ?: Response::HTTP_BAD_REQUEST,
                detail: $e->getMessage() ?: 'The request could not be understood by the server due to malformed syntax.',
            );
        });

        // Generic HTTP Exceptions
        $exceptions->render(function (HttpExceptionInterface $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                typeKey: 'default',
                title: __('auth.http_forbidden'),
                status: $e->getCode() ?: Response::HTTP_FORBIDDEN,
                detail: $e->getMessage() ?: 'You are not authorised to perform this action.',
            );
        });

        // Fatal / Internal Server Errors (500)
        $exceptions->render(function (Throwable $e, Request $request): ProblemResponse {
            $detail = config()->boolean('app.debug') ? $e->getMessage() : 'An internal server error occurred.';

            return new ProblemResponse(
                typeKey: 'internal_error',
                title: __('auth.http_internal_error'),
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                detail: $detail,
            );
        });
    })
    ->create();
