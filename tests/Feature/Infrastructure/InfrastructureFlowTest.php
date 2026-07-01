<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Http\Middleware\SetLocaleMiddleware;
use App\Http\Middleware\TraceIdMiddleware;
use App\Http\Responses\SuccessResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

test('Happy Path: Request -> Middleware -> Action -> SuccessResponse', function () {
    Route::get('/api/v1/flow-happy', function (Request $request) {
        $action = new class
        {
            public function handle(string $name): array
            {
                return [
                    'message' => "Hello, {$name}!",
                    'timestamp' => now()->toIso8601String(),
                ];
            }
        };
        $result = $action->handle($request->query('name', 'Guest'));

        return new SuccessResponse(
            title: 'OK',
            detail: 'Flow executed successfully.',
            data: $result
        );
    })->middleware([
        TraceIdMiddleware::class,
        SetLocaleMiddleware::class,
    ]);

    $response = $this->get('/api/v1/flow-happy?name=Jules', [
        'Accept-Language' => 'en',
    ]);

    expect($response)->toBeSuccessResponse(status: 200, title: 'OK')
        ->toHaveTraceId();

    expect($response->json('data.message'))->toBe('Hello, Jules!');
});

test('Error Path: Validation -> Exception Handler -> ProblemResponse', function () {
    Route::post('/api/v1/flow-error', function (Request $request) {
        $request->validate(['email' => 'required|email']);

        return response()->json(['ok' => true]);
    });

    $response = $this->postJson('/api/v1/flow-error', ['email' => 'invalid-email']);

    expect($response)->toBeProblemResponse(status: 422, type: 'validation');

    expect($response->json('errors'))->toHaveKey('email');
});

test('Auth Flow: Unauthenticated -> ProblemResponse', function () {
    Route::get('/api/v1/flow-protected', fn () => response()->json(['ok' => true]))
        ->middleware('auth:sanctum');

    $response = $this->getJson('/api/v1/flow-protected');

    expect($response)->toBeProblemResponse(status: 401);
});
