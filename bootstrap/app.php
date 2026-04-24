<?php

use App\Http\Middleware\SetLocaleMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        ]);

        $middleware->append(SetLocaleMiddleware::class);

        $middleware->statefulApi();
        // $middleware->throttleApi(); // We will define custom throttle in routes
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*'));

        $exceptions->render(function (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.resource_not_found'),
                'errors' => [],
            ], 404);
        });

        $exceptions->render(function (AuthenticationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.unauthenticated'),
                'errors' => [],
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.unauthorized'),
                'errors' => [],
            ], 403);
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.route_not_found'),
                'errors' => [],
            ], 404);
        });
    })
    ->create();
