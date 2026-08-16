<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Routing\Router;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Render an exception through the global exception handler as an API request.
 *
 * @return TestResponse<Response>
 */
function renderApiException(Throwable $e, string $path = '/api/v1/anything'): TestResponse
{
    $request = Request::create($path);

    $rendered = app(Handler::class)->render($request, $e);

    return TestResponse::fromBaseResponse(Router::toResponse($request, $rendered));
}

describe('ExceptionHandler', function () {

    describe('validation', function () {

        it('renders 422 problem response with field errors', function () {
            $response = renderApiException(ValidationException::withMessages(['email' => ['The email field is required.']]));

            assertProblemResponse($response, Response::HTTP_UNPROCESSABLE_ENTITY, 'validation-failed');

            expect($response->json('errors.email'))->toBe(['The email field is required.']);
        })->group('smoke');

    });

    describe('authentication', function () {

        it('renders 401 unauthenticated problem response', function () {
            $response = renderApiException(new AuthenticationException('Unauthenticated.'));

            assertProblemResponse($response, Response::HTTP_UNAUTHORIZED, 'authentication-required');

            expect($response->json('detail'))->toBe('Unauthenticated.');
        })->group('smoke');

    });

    describe('forbidden', function () {

        it('renders 403 problem response for access denied', function () {
            $response = renderApiException(new AccessDeniedHttpException('Access denied.'));

            assertProblemResponse($response, Response::HTTP_FORBIDDEN, 'access-denied');

            expect($response->json('detail'))->toBe('Access denied.');
        })->group('smoke');

        it('renders 403 problem response for authorization exception', function () {
            $response = renderApiException(new AuthorizationException('This action is unauthorized.'));

            assertProblemResponse($response, Response::HTTP_FORBIDDEN, 'access-denied');
        })->group('smoke');

        it('uses the invalid signature detail for invalid signature exceptions', function () {
            $response = renderApiException(new InvalidSignatureException);

            assertProblemResponse($response, Response::HTTP_FORBIDDEN, 'access-denied');

            expect($response->json('detail'))->toBe(__('auth.invalid_signature'));
        })->group('smoke');

        it('forwards custom headers from access denied exceptions', function () {
            $response = renderApiException(new AccessDeniedHttpException(
                'Access denied.',
                null,
                0,
                ['X-Custom-Header' => 'custom-value'],
            ));

            $response->assertHeader('X-Custom-Header', 'custom-value');
        })->group('smoke');

    });

    describe('not found', function () {

        it('renders 404 problem response for missing routes', function () {
            $response = renderApiException(new NotFoundHttpException);

            assertProblemResponse($response, Response::HTTP_NOT_FOUND, 'resource-not-found');

            expect($response->json('detail'))->toBe(__('auth.not_found_detail'));
        })->group('smoke');

        it('renders 404 problem response for missing models', function () {
            $response = renderApiException(new ModelNotFoundException);

            assertProblemResponse($response, Response::HTTP_NOT_FOUND, 'resource-not-found');
        })->group('smoke');

        it('forwards custom headers from not found exceptions', function () {
            $response = renderApiException(new NotFoundHttpException(
                'Resource missing.',
                null,
                0,
                ['X-Custom-Header' => 'custom-value'],
            ));

            $response->assertHeader('X-Custom-Header', 'custom-value');
        })->group('smoke');

    });

    describe('rate limit', function () {

        it('renders 429 rate_limited problem response', function () {
            $response = renderApiException(new TooManyRequestsHttpException(60));

            assertProblemResponse($response, Response::HTTP_TOO_MANY_REQUESTS, 'rate-limit-exceeded');

            $response->assertHeader('Retry-After', '60');

            expect($response->json('detail'))->toBe(__('auth.rate_limited_detail'));
        })->group('smoke');

    });

    describe('bad request', function () {

        it('renders 400 problem response for invalid arguments', function () {
            $response = renderApiException(new InvalidArgumentException('Invalid request payload.'));

            assertProblemResponse($response, Response::HTTP_BAD_REQUEST, 'invalid-request-payload');

            expect($response->json('detail'))->toBe('Invalid request payload.');
        })->group('smoke');

    });

    describe('generic http exceptions', function () {

        it('preserves the exception status code', function () {
            $response = renderApiException(new HttpException(Response::HTTP_I_AM_A_TEAPOT, 'I am a teapot'));

            assertProblemResponse($response, Response::HTTP_I_AM_A_TEAPOT, 'general-error');

            expect($response->json('detail'))->toBe('I am a teapot');
        })->group('smoke');

        it('forwards custom string headers from the exception', function () {
            $response = renderApiException(new HttpException(
                Response::HTTP_I_AM_A_TEAPOT,
                'I am a teapot',
                null,
                ['X-Custom-Header' => 'custom-value'],
            ));

            $response->assertHeader('X-Custom-Header', 'custom-value');
        })->group('smoke');

        it('stringifies integer header values', function () {
            $response = renderApiException(new HttpException(
                Response::HTTP_I_AM_A_TEAPOT,
                'I am a teapot',
                null,
                ['X-Retry-Count' => 3],
            ));

            $response->assertHeader('X-Retry-Count', '3');
        })->group('smoke');

        it('forwards multi-value headers from the exception', function () {
            $response = renderApiException(new HttpException(
                Response::HTTP_I_AM_A_TEAPOT,
                'I am a teapot',
                null,
                ['Link' => [
                    '<https://v2.example.com>; rel="successor-version"',
                    '<https://v3.example.com>; rel="successor-version"',
                ]],
            ));

            expect($response->headers->all('Link'))->toBe([
                '<https://v2.example.com>; rel="successor-version"',
                '<https://v3.example.com>; rel="successor-version"',
            ]);
        })->group('smoke');

    });

    describe('internal errors', function () {

        it('renders 500 problem response hiding details when debug is off', function () {
            config()->set('app.debug', false);

            $response = renderApiException(new RuntimeException('Top secret failure'));

            assertProblemResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR, 'server-error');

            expect($response->json('detail'))->toBe(__('auth.internal_error_detail'));
        })->group('smoke');

        it('renders 500 problem response with details when debug is on', function () {
            config()->set('app.debug', true);

            $response = renderApiException(new RuntimeException('Top secret failure'));

            assertProblemResponse($response, Response::HTTP_INTERNAL_SERVER_ERROR, 'server-error');

            expect($response->json('detail'))->toBe('Top secret failure');
        })->group('smoke');

    });

    describe('request agnostic rendering', function () {

        it('renders problem responses even for non-api requests', function () {
            $response = renderApiException(new NotFoundHttpException, '/');

            assertProblemResponse($response, Response::HTTP_NOT_FOUND, 'resource-not-found');
        })->group('smoke');

    });

});
