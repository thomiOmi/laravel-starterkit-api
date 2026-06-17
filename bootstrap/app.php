<?php

use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\PlanFeatureMiddleware;
use App\Http\Middleware\SetLocaleMiddleware;
use App\Http\Middleware\Sunset;
use App\Http\Middleware\TraceIdMiddleware;
use App\Http\Responses\ProblemResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Validation\ValidationException;
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
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'plan.feature' => PlanFeatureMiddleware::class,
            'force.json' => ForceJsonResponse::class,
            'sunset' => Sunset::class,
            'trace.id' => TraceIdMiddleware::class,
        ]);

        $middleware->priority([
            AuthenticatesRequests::class,
            Authenticate::class,
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->append(SetLocaleMiddleware::class);
        $middleware->prependToGroup('api', TraceIdMiddleware::class);

        $middleware->statefulApi();
        // $middleware->throttleApi(); // We will define custom throttle in routes
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));

        $exceptions->render(function (ValidationException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                title: __('auth.validation_failed'),
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                detail: 'The given data was invalid.',
                typeKey: 'validation',
                errors: $e->errors(),
                instance: $request->url(),
            );
        });

        $exceptions->render(function (AuthenticationException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                title: __('auth.unauthenticated'),
                status: Response::HTTP_UNAUTHORIZED,
                detail: 'You must be authenticated to access this resource.',
                typeKey: 'unauthenticated',
                instance: $request->url(),
            );
        });

        $exceptions->render(function (InvalidSignatureException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                title: 'Invalid Signature',
                status: Response::HTTP_FORBIDDEN,
                detail: 'The request signature is invalid or has expired.',
                typeKey: 'forbidden',
                instance: $request->url(),
            );
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                title: __('auth.forbidden'),
                status: Response::HTTP_FORBIDDEN,
                detail: 'You are not authorised to perform this action.',
                typeKey: 'forbidden',
                instance: $request->url(),
            );
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                title: __('auth.not_found'),
                status: Response::HTTP_NOT_FOUND,
                detail: $e->getMessage() ?: 'The requested URL does not exist.',
                typeKey: 'not_found',
                instance: $request->url(),
            );
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                title: __('auth.too_many_requests'),
                status: Response::HTTP_TOO_MANY_REQUESTS,
                detail: 'You have exceeded the request rate limit. Please try again later.',
                typeKey: 'rate_limited',
                instance: $request->url(),
            );
        });

        $exceptions->render(function (InvalidArgumentException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                title: 'Bad Request',
                status: Response::HTTP_BAD_REQUEST,
                detail: $e->getMessage(),
                typeKey: 'bad_request',
                instance: $request->url(),
            );
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                title: $e->getMessage() ?: 'HTTP Error',
                status: $e->getStatusCode(),
                detail: $e->getMessage() ?: 'An HTTP error occurred.',
                typeKey: 'about:blank',
                instance: $request->url(),
            );
        });

        $exceptions->render(function (Throwable $e, Request $request): ProblemResponse {
            $detail = 'An internal server error occurred.';

            if (config('app.debug', false)) {
                $detail = $e->getMessage();
            }

            $payload = new ProblemResponse(
                title: 'Internal Server Error',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                detail: $detail,
                typeKey: 'internal_error',
                instance: $request->url(),
            );

            if (config('app.debug', false)) {
                $payload->setData(array_merge(
                    (array) $payload->getData(true),
                    [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTrace(),
                    ],
                ));
            }

            return $payload;
        });
    })
    ->create();
