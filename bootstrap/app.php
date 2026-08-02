<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\FeatureFlagMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
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
use Illuminate\Routing\Router;
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
            'verified' => EnsureEmailIsVerified::class,
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
        $middleware->prependToGroup('api', SecurityHeadersMiddleware::class);

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
                status: $e->getCode() !== 0 ? $e->getCode() : Response::HTTP_UNPROCESSABLE_ENTITY,
                detail: $e->getMessage() !== '' ? $e->getMessage() : __('auth.validation_failed'),
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
                status: $e->getCode() !== 0 ? $e->getCode() : Response::HTTP_UNAUTHORIZED,
                detail: $e->getMessage() !== '' ? $e->getMessage() : __('auth.unauthenticated'),
            );
        });

        // Access Denied / Forbidden (403)
        $exceptions->render(function (AccessDeniedHttpException|InvalidSignatureException|AuthorizationException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                typeKey: 'forbidden',
                title: __('auth.http_forbidden'),
                status: $e->getCode() !== 0 ? $e->getCode() : Response::HTTP_FORBIDDEN,
                detail: match (true) {
                    $e instanceof InvalidSignatureException => __('auth.invalid_signature'),
                    $e->getMessage() !== '' => $e->getMessage(),
                    default => __('auth.access_denied'),
                }
            );
        });

        // Not Found Exception (404)
        $exceptions->render(function (NotFoundHttpException|ModelNotFoundException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                typeKey: 'not_found',
                title: __('auth.http_not_found'),
                status: $e->getCode() !== 0 ? $e->getCode() : Response::HTTP_NOT_FOUND,
                detail: $e->getMessage() !== '' ? $e->getMessage() : __('auth.not_found_detail'),
            );
        });

        // Too Many Requests Exception (429)
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                typeKey: 'rate_limited',
                title: __('auth.http_too_many_requests'),
                status: $e->getCode() !== 0 ? $e->getCode() : Response::HTTP_TOO_MANY_REQUESTS,
                detail: $e->getMessage() !== '' ? $e->getMessage() : __('auth.rate_limited_detail'),
            );
        });

        // Invalid Argument Exception (400)
        $exceptions->render(function (InvalidArgumentException $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                typeKey: 'bad_request',
                title: __('auth.http_bad_request'),
                status: $e->getCode() !== 0 ? $e->getCode() : Response::HTTP_BAD_REQUEST,
                detail: $e->getMessage() !== '' ? $e->getMessage() : __('auth.bad_request_detail'),
            );
        });

        // Generic HTTP Exceptions (preserve the exception's status code)
        $exceptions->render(function (HttpExceptionInterface $e, Request $request): ProblemResponse {
            return new ProblemResponse(
                typeKey: 'default',
                title: __('auth.http_forbidden'),
                status: $e->getStatusCode(),
                detail: $e->getMessage() !== '' ? $e->getMessage() : __('auth.access_denied'),
            );
        });

        // Fatal / Internal Server Errors (500)
        $exceptions->render(function (Throwable $e, Request $request): ProblemResponse {
            $detail = config()->boolean('app.debug') ? $e->getMessage() : __('auth.internal_error_detail');

            return new ProblemResponse(
                typeKey: 'internal_error',
                title: __('auth.http_internal_error'),
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                detail: $detail,
            );
        });

        // Attach trace and security headers to exception responses.
        // Middleware that sets headers after `$next()` never runs when the
        // request throws, so re-apply the same headers here.
        $exceptions->respond(function (ProblemResponse|Response $response, Throwable $e, Request $request): Response {
            $response = Router::toResponse($request, $response);

            return (new TraceIdMiddleware)->handle($request, function () use ($response, $request): Response {
                return (new SecurityHeadersMiddleware)->handle($request, function () use ($response): Response {
                    return $response;
                });
            });
        });
    })
    ->create();
