<?php

use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\PlanFeatureMiddleware;
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
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;
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
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*'));

        $errorTypeBaseUrl = config('app.url', 'about:blank');
        assert(is_string($errorTypeBaseUrl));

        $exceptions->render(function (ValidationException $e, Request $request) use ($errorTypeBaseUrl): ProblemResponse {
            return new ProblemResponse(
                title: __('auth.validation_failed'),
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                detail: 'The given data was invalid.',
                type: $errorTypeBaseUrl.'/validation-error',
                errors: $e->errors(),
                instance: $request->path(),
            );
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($errorTypeBaseUrl): ProblemResponse {
            return new ProblemResponse(
                title: __('auth.unauthenticated'),
                status: Response::HTTP_UNAUTHORIZED,
                detail: 'You must be authenticated to access this resource.',
                type: $errorTypeBaseUrl.'/unauthenticated',
            );
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($errorTypeBaseUrl): ProblemResponse {
            return new ProblemResponse(
                title: __('auth.forbidden'),
                status: Response::HTTP_FORBIDDEN,
                detail: 'You are not authorised to perform this action.',
                type: $errorTypeBaseUrl.'/forbidden',
            );
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($errorTypeBaseUrl): ProblemResponse {
            return new ProblemResponse(
                title: __('auth.not_found'),
                status: Response::HTTP_NOT_FOUND,
                detail: 'The requested resource does not exist.',
                type: $errorTypeBaseUrl.'/not-found',
                instance: $request->path(),
            );
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($errorTypeBaseUrl): ProblemResponse {
            return new ProblemResponse(
                title: __('auth.not_found'),
                status: Response::HTTP_NOT_FOUND,
                detail: $e->getMessage() ?: 'The requested URL does not exist.',
                type: $errorTypeBaseUrl.'/not-found',
                instance: $request->path(),
            );
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) use ($errorTypeBaseUrl): ProblemResponse {
            return new ProblemResponse(
                title: __('auth.too_many_requests'),
                status: Response::HTTP_TOO_MANY_REQUESTS,
                detail: 'You have exceeded the request rate limit. Please try again later.',
                type: $errorTypeBaseUrl.'/rate-limited',
            );
        });

        $exceptions->render(function (InvalidArgumentException $e, Request $request) use ($errorTypeBaseUrl): ProblemResponse {
            return new ProblemResponse(
                title: 'Bad Request',
                status: Response::HTTP_BAD_REQUEST,
                detail: $e->getMessage(),
                type: $errorTypeBaseUrl.'/bad-request',
                instance: $request->path(),
            );
        });
    })
    ->create();
