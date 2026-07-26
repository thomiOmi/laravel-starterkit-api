<?php

declare(strict_types=1);

use App\Http\Responses\ProblemResponse;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;

beforeEach(function () {
    config()->set('errors.docs_url', 'https://docs.example.com/problems');
    config()->set('errors.types.default', 'general-error');
    config()->set('errors.types.validation', 'validation-failed');
});

test('returns default error structure', function () {
    $response = (new ProblemResponse(detail: 'Something went wrong'))->toResponse(new Request);

    expect($response->getStatusCode())->toBe(400);
    expect($response->headers->get('Content-Type'))->toBe('application/problem+json');

    $data = $response->getData(true);

    expect($data['type'])->toBe('https://docs.example.com/problems/general-error');
    expect($data['title'])->toBe('Bad Request');
    expect($data['status'])->toBe(400);
    expect($data['detail'])->toBe('Something went wrong');
    expect($data)->toHaveKey('timestamp');
});

test('uses custom title when provided', function () {
    $response = (new ProblemResponse(
        typeKey: 'validation',
        title: 'Validation Failed',
        status: 422,
        detail: 'The given data was invalid.',
    ))->toResponse(new Request);

    $data = $response->getData(true);

    expect($data['title'])->toBe('Validation Failed');
    expect($data['type'])->toBe('https://docs.example.com/problems/validation-failed');
    expect($data['status'])->toBe(422);
});

test('falls back to HTTP status text when title is empty', function () {
    $response = (new ProblemResponse(
        title: '',
        status: 404,
        detail: 'Not found',
    ))->toResponse(new Request);

    $data = $response->getData(true);

    expect($data['title'])->toBe('Not Found');
});

test('uses about:blank type URI', function () {
    $response = (new ProblemResponse(
        typeKey: 'about:blank',
        detail: 'Custom error',
    ))->toResponse(new Request);

    $data = $response->getData(true);

    expect($data['type'])->toBe('about:blank');
});

test('includes instance URI', function () {
    $response = (new ProblemResponse(
        detail: 'Error',
        instance: '/api/v1/users/123',
    ))->toResponse(new Request);

    $data = $response->getData(true);

    expect($data['instance'])->toBe('/api/v1/users/123');
});

test('omits instance when empty', function () {
    $response = (new ProblemResponse(detail: 'Error'))->toResponse(new Request);

    $data = $response->getData(true);

    expect($data)->not->toHaveKey('instance');
});

test('includes extension members', function () {
    $response = (new ProblemResponse(
        detail: 'Error',
        extensions: ['errors' => ['name' => ['Required']]],
    ))->toResponse(new Request);

    $data = $response->getData(true);

    expect($data['errors'])->toBe(['name' => ['Required']]);
});

test('converts Arrayable extensions to array', function () {
    $arrayable = new class implements Arrayable
    {
        public function toArray(): array
        {
            return ['key' => 'value'];
        }
    };

    $response = (new ProblemResponse(
        detail: 'Error',
        extensions: ['meta' => $arrayable],
    ))->toResponse(new Request);

    $data = $response->getData(true);

    expect($data['meta'])->toBe(['key' => 'value']);
});

test('filters protected keys from extensions', function () {
    $response = (new ProblemResponse(
        detail: 'Error',
        extensions: ['status' => 'should_not_override', 'custom' => 'ok'],
    ))->toResponse(new Request);

    $data = $response->getData(true);

    expect($data['custom'])->toBe('ok');
    expect($data['status'])->toBe(400);
});

test('merges custom headers', function () {
    $response = (new ProblemResponse(
        detail: 'Error',
        headers: ['X-Request-ID' => 'abc-123'],
    ))->toResponse(new Request);

    expect($response->headers->get('X-Request-ID'))->toBe('abc-123');
    expect($response->headers->get('Content-Type'))->toBe('application/problem+json');
});

test('uses unknown status text fallback', function () {
    $response = (new ProblemResponse(
        title: '',
        status: 499,
        detail: 'Custom error',
    ))->toResponse(new Request);

    $data = $response->getData(true);

    expect($data['title'])->toBe('Unknown Error');
});
